<?php

namespace markhuot\craftpest\http;

use craft\web\User;
use markhuot\craftpest\http\requests\GetRequest;
use markhuot\craftpest\http\requests\PostRequest;
use markhuot\craftpest\http\requests\WebRequest;
use markhuot\craftpest\web\TestableResponse;
use yii\web\Cookie;

class RequestBuilder
{
    private readonly WebRequest $request;

    private readonly \craft\web\Application $app;

    private readonly RequestHandler $handler;

    protected array $body = [];

    protected array $originalGlobals;

    public function __construct(
        protected string $method,
        string $uri,
        ?\craft\web\Application $app = null,
        ?RequestHandler $handler = null,
    ) {
        $this->app = $app ?? \Craft::$app;
        $this->handler = $handler ?? new RequestHandler($this->app);
        $this->request = $this->prepareRequest($this->method, $uri);
    }

    public function addHeader(string $name, $value): self
    {
        $this->request->headers->add($name, $value);

        return $this;
    }

    public function addCookie(string $key, $value, $expire = 0): self
    {
        $this->request->cookies->add(new Cookie([
            'name' => $key,
            'value' => $value,
            'expire' => $expire,
        ]));

        return $this;
    }

    public function setBody(array $body): self
    {
        $this->body = array_merge($this->body, $body);

        return $this;
    }

    public function withCsrfToken(): self
    {
        $this->body['CRAFT_CSRF_TOKEN'] = $this->request->getCsrfToken();

        return $this;
    }

    public function setReferrer(?string $value): self
    {
        $this->request->headers->set('Referer', $value);

        return $this;
    }

    public function asUser(User|string $user): self
    {
        // TODO
        return $this;
    }

    public function send(): TestableResponse
    {
        $skipSpecialHandling = false;

        $this->setGlobals();
        $response = $this->handler->handle($this->request, $skipSpecialHandling);
        $this->resetGlobals();

        return $response;
    }

    protected function setGlobals()
    {
        $this->originalGlobals['_POST'] = array_merge($_POST);
        $this->originalGlobals['_SERVER'] = array_merge($_SERVER);
        $_SERVER['HTTP_METHOD'] = $this->method;
        $this->request->headers->add('X-Http-Method-Override', $this->method);

        if (\Craft::$app->config->getGeneral()->devMode) {
            $this->request->headers->add('X-Debug', 'enable');
        }

        $_POST = $body = $this->body ?? [];

        if ($body !== []) {
            $contentType = $this->request->getContentType();
            $isJson = str_contains($contentType, 'json');

            // Not needed just yet. If we add more content-types we'll need
            // to add more to this conditional
            // $isFormData = strpos($contentType, 'form-data') !== false;

            $this->request->setBody(
                $isJson ? json_encode($body) : http_build_query($body)
            );
            $this->request->headers->add('content-type',
                $isJson ? 'application/json' : 'multipart/form-data'
            );
        }
    }

    protected function resetGlobals()
    {
        $_POST = $this->originalGlobals['_SERVER'] ?? [];
        $this->originalGlobals = [];
    }

    /**
     * Pre-populate the request object
     */
    private function prepareRequest(string $method, string $uri): WebRequest
    {
        return match (strtolower($method)) {
            'get' => GetRequest::make($uri),
            'post' => PostRequest::make($uri),
            default => throw new \InvalidArgumentException("Unable to build request. Unknown method '$method'"),
        };
    }
}
