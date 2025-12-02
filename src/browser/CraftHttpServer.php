<?php

namespace markhuot\craftpest\browser;

use Amp\ByteStream\ReadableResourceStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\HttpServer as AmpHttpServer;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Craft;
use markhuot\craftpest\http\RequestHandler;
use markhuot\craftpest\http\requests\GetRequest;
use Pest\Browser\Exceptions\ServerNotFoundException;
use Pest\Browser\Execution;
use Pest\Browser\GlobalState;
use Psr\Log\NullLogger;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

class CraftHttpServer implements \Pest\Browser\Contracts\HttpServer
{
    /**
     * The URL path used for visitTemplate() requests.
     */
    public const TEMPLATE_RENDER_PATH = '/__craftpest_template';

    /**
     * The underlying socket server instance, if any.
     */
    private ?AmpHttpServer $socket = null;

    /**
     * The last throwable that occurred during the server's execution.
     */
    private ?Throwable $lastThrowable = null;

    /**
     * The Craft request handler instance.
     */
    private RequestHandler $requestHandler;

    /**
     * Creates a new Craft http server instance.
     */
    public function __construct(
        public readonly string $host,
        public readonly int $port,
    ) {
        $this->requestHandler = new RequestHandler;
    }

    /**
     * Destroy the server instance and stop listening for incoming connections.
     */
    public function __destruct()
    {
        // $this->stop();
    }

    /**
     * Get the base path for static assets.
     */
    protected function getBasePath(): string
    {
        return \Craft::getAlias('@webroot');
    }

    /**
     * Start the server and listen for incoming connections.
     */
    public function start(): void
    {
        if ($this->socket instanceof AmpHttpServer) {
            return;
        }

        $this->socket = $server = SocketHttpServer::createForDirectAccess(new NullLogger);

        $server->expose("{$this->host}:{$this->port}");
        $server->start(
            new ClosureRequestHandler($this->handleRequest(...)),
            new DefaultErrorHandler,
        );
    }

    /**
     * Stop the server and close all connections.
     */
    public function stop(): void
    {
        if ($this->socket instanceof AmpHttpServer) {
            $this->flush();

            if ($this->socket instanceof AmpHttpServer) {
                if (in_array($this->socket->getStatus(), [HttpServerStatus::Starting, HttpServerStatus::Started], true)) {
                    $this->socket->stop();
                }

                $this->socket = null;
            }
        }
    }

    /**
     * Rewrite the given URL to match the server's host and port.
     */
    public function rewrite(string $url): string
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = mb_ltrim($url, '/');
            $url = '/'.$url;
        }

        $parts = parse_url($url);
        $queryParameters = [];
        $path = $parts['path'] ?? '/';
        parse_str($parts['query'] ?? '', $queryParameters);

        // Build the URL manually
        $baseUrl = $this->url();
        $fullUrl = rtrim($baseUrl, '/').$path;

        if (! empty($queryParameters)) {
            $fullUrl .= '?'.http_build_query($queryParameters);
        }

        return $fullUrl;
    }

    /**
     * Flush pending requests and close all connections.
     */
    public function flush(): void
    {
        if (! $this->socket instanceof AmpHttpServer) {
            return;
        }

        Execution::instance()->tick();

        $this->lastThrowable = null;
    }

    /**
     * Bootstrap the server and set the application URL.
     */
    public function bootstrap(): void
    {
        $this->start();

        // Craft doesn't need special URL configuration for browser testing
        // The server URL will be used automatically through the request context
    }

    /**
     * Get the last throwable that occurred during the server's execution.
     */
    public function lastThrowable(): ?Throwable
    {
        return $this->lastThrowable;
    }

    /**
     * Throws the last throwable if it should be thrown.
     *
     * @throws Throwable
     */
    public function throwLastThrowableIfNeeded(): void
    {
        if (! $this->lastThrowable instanceof Throwable) {
            return;
        }

        // In Craft/Pest, we check if we should render exceptions as HTML
        // If not, we should throw them for the test to catch
        if (! \markhuot\craftpest\helpers\test\test()->shouldRenderExceptionsAsHtml()) {
            throw $this->lastThrowable;
        }
    }

    /**
     * Get the server URL.
     */
    private function url(): string
    {
        if (! $this->socket instanceof AmpHttpServer) {
            throw new ServerNotFoundException('The HTTP server is not running.');
        }

        return sprintf('http://%s:%d', $this->host, $this->port);
    }

    /**
     * Handle the incoming request and return a response.
     */
    private function handleRequest(AmpRequest $request): Response
    {
        GlobalState::flush();

        if (Execution::instance()->isWaiting() === false) {
            Execution::instance()->tick();
        }

        $uri = $request->getUri();
        $path = in_array($uri->getPath(), ['', '0'], true) ? '/' : $uri->getPath();
        $query = $uri->getQuery();
        $fullPath = $path.($query !== '' && $query !== null ? '?'.$query : '');
        $absoluteUrl = mb_rtrim($this->url(), '/').$fullPath;

        // Check if this is a template render request from visitTemplate()
        if ($path === self::TEMPLATE_RENDER_PATH) {
            parse_str($query, $queryParams);

            return $this->renderTemplate(
                $queryParams['template'] ?? '',
                json_decode($queryParams['params'] ?? '[]', true) ?? [],
            );
        }

        // Check if this is a static asset request
        $filepath = $this->getBasePath().$path;
        if (file_exists($filepath) && ! is_dir($filepath)) {
            return $this->asset($filepath);
        }

        // Create a Craft web request
        $contentType = $request->getHeader('content-type') ?? '';
        $method = mb_strtoupper($request->getMethod());
        $rawBody = (string) $request->getBody();
        $bodyParams = [];

        if ($method !== 'GET' && str_starts_with(mb_strtolower($contentType), 'application/x-www-form-urlencoded')) {
            parse_str($rawBody, $bodyParams);
        } elseif ($method !== 'GET' && str_starts_with(mb_strtolower($contentType), 'application/json')) {
            $bodyParams = json_decode($rawBody, true) ?? [];
        }

        // Create the request using the factory method
        $craftRequest = GetRequest::make($absoluteUrl);

        // Override properties for browser server context
        $craftRequest->setHostInfo($this->url());
        $craftRequest->setHostName($this->host);
        $craftRequest->setPort($this->port);
        $craftRequest->setBody($rawBody);
        $craftRequest->setBodyParams($bodyParams);

        // Set headers
        foreach ($request->getHeaders() as $name => $values) {
            $value = implode(', ', $values);
            $craftRequest->headers->set($name, $value);
        }

        // Set cookies
        $cookies = new \yii\web\CookieCollection(['readOnly' => false]);
        foreach ($request->getCookies() as $name => $value) {
            $cookies->add(new \yii\web\Cookie([
                'name' => $name,
                'value' => $value,
            ]));
        }
        $craftRequest->setCookies($cookies);

        // Override the method if needed
        if ($method !== 'GET') {
            $craftRequest->headers->set('X-Http-Method-Override', $method);
        }

        try {
            $craftResponse = $this->requestHandler->handle($craftRequest);
        } catch (Throwable $e) {
            $this->lastThrowable = $e;
            throw $e;
        }

        // Check if the response has an exception
        if (property_exists($craftResponse, 'exception') && $craftResponse->exception !== null) {
            $this->lastThrowable = $craftResponse->exception;
        }

        $content = $craftResponse->content;

        if ($content === null) {
            try {
                ob_start();
                $craftResponse->send();
            } finally {
                $content = mb_trim(ob_get_clean() ?: '');
            }
        }

        return new Response(
            $craftResponse->statusCode ?? 200,
            $craftResponse->headers->toArray(),
            $content,
        );
    }

    /**
     * Return an asset response.
     */
    private function asset(string $filepath): Response
    {
        $file = fopen($filepath, 'r');

        if ($file === false) {
            return new Response(404);
        }

        $mimeTypes = new MimeTypes;
        $contentType = $mimeTypes->getMimeTypes(pathinfo($filepath, PATHINFO_EXTENSION));

        $contentType = $contentType[0] ?? 'application/octet-stream';

        return new Response(200, [
            'Content-Type' => $contentType,
        ], new ReadableResourceStream($file));
    }

    /**
     * Render a template and return the response.
     *
     * @param  array<string, mixed>  $params
     */
    private function renderTemplate(string $template, array $params): Response
    {
        if ($template === '') {
            return new Response(400, [], 'Template path is required.');
        }

        try {
            $content = Craft::$app->getView()->renderTemplate($template, $params);

            return new Response(200, [
                'Content-Type' => 'text/html; charset=utf-8',
            ], $content);
        } catch (Throwable $e) {
            $this->lastThrowable = $e;

            return new Response(500, [
                'Content-Type' => 'text/html; charset=utf-8',
            ], sprintf(
                '<html><body><h1>Template Render Error</h1><pre>%s</pre></body></html>',
                htmlspecialchars($e->getMessage()),
            ));
        }
    }
}
