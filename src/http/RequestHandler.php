<?php

namespace markhuot\craftpest\http;

use craft\web\twig\Extension;
use markhuot\craftpest\http\requests\WebRequest;
use markhuot\craftpest\web\TestableResponse;
use Twig\Error\RuntimeError;
use yii\base\ExitException;

use function markhuot\craftpest\helpers\test\test;

class RequestHandler
{
    private \craft\web\Application $app;

    public function __construct(?\craft\web\Application $app = null)
    {
        $this->app = $app ?? \Craft::$app;
    }

    public function handle($request, $skipSpecialHandling = false): TestableResponse
    {
        $obLevel = ob_get_level();
        $this->registerWithCraft($request);

        try {
            $this->app->trigger(\craft\web\Application::EVENT_BEFORE_REQUEST);

            // The actual call
            /** @var TestableResponse $response */
            $response = $this->app->handleRequest($request, $skipSpecialHandling);
            $response->setRequest($request);
            $response->prepare();

            test()->storeCookieCollection($response->cookies);

            return $response;
        } catch (\Throwable $exception) {
            return $this->handleException($exception, $request);
        } finally {
            // Clear out output buffering that may still be left open because of an exception. Ideally
            // we wouldn't need this but Yii/Craft leaves something open somewhere that we're not
            // handling correctly here.
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            // Always send the after request so Craft can clean up after itself
            $this->app->trigger(\craft\web\Application::EVENT_AFTER_REQUEST);
        }
    }

    protected function handleException(\Throwable $exception, WebRequest $request): TestableResponse
    {
        // Twig is _so_ annoying that it wraps actual exceptions. So we need to unpack any
        // twig exceptions and recursively interact with the actual/previous exception.
        if ($exception instanceof RuntimeError) {
            return $this->handleException($exception->getPrevious(), $request);
        }

        // If our test doesn't want us to render exceptions out as HTML then re-throw the
        // exception so the test case can catch it and make assertions against the exception.
        if (! test()->shouldRenderExceptionsAsHtml()) {
            throw $exception;
        }

        if ($exception instanceof ExitException) {
            /** @var TestableResponse $response */
            $response = \Craft::$app->response;
        } else {
            // Fake a response and set the HTTP status code
            $response = \Craft::createObject(TestableResponse::class);
            $response->setStatusCode($exception->statusCode ?? 500);
            $response->setRequest($request);
        }

        // Error response
        return $response;
    }

    protected function registerWithCraft($request): void
    {
        // Don't run the queue automatically, because it injects JS in to pages that might not be expecting
        // them under test
        $this->app->getConfig()->getGeneral()->runQueueAutomatically = false;

        // The next request
        $this->app->set('request', $request);

        // A response object with methods for assertions
        // Yii will fill it with data once the response is successful
        $response = \Craft::createObject(TestableResponse::class);

        // Copy over any behaviors from the original response
        $response->attachBehaviors($this->app->response->behaviors);

        // Set the new response in the container
        $this->app->set('response', $response);

        $this->app->setComponents([
            'request' => $request,
            'response' => $response,

            // Since we just modified the request on demand a lot of Craft's native assumptions
            // are out of date. Craft works off a request/response paradigm and by sending
            // multiple requests through a single instance of the Craft application it can get
            // confused.
            // We'll help out by resetting a few components (causing them to recalculate their
            // internal state). The config here is no different than the default config.
            'urlManager' => [
                'class' => \craft\web\UrlManager::class,
                'enablePrettyUrl' => true,
                'ruleConfig' => ['class' => \craft\web\UrlRule::class],
            ],
        ]);

        // Pull out our view service
        $view = \Craft::$app->getView();

        // Set the correct template mode
        $view->setTemplateMode($request->isCpRequest ? 'cp' : 'site');

        // Update the Twig globals. Normally PHP/Craft operate in a single request/response
        // lifecycle. However, because we're sending multiple requests through a single
        // Craft instance we need to manually update the globals
        $globals = (new Extension($view, $view->getTwig()))->getGlobals();
        foreach ($globals as $key => $value) {
            $view->getTwig()->addGlobal($key, $value);
        }
    }
}
