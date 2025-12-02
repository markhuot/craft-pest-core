<?php

namespace markhuot\craftpest\browser;

/**
 * Registry to store template paths and parameters for browser testing.
 *
 * This allows `visitTemplate()` to register a template render request
 * with a unique token, which CraftHttpServer can then use to render
 * the template when the browser visits the special URL.
 */
class TemplateRenderRegistry
{
    /**
     * Stored template render requests keyed by token.
     *
     * @var array<string, array{template: string, params: array<string, mixed>}>
     */
    private static array $requests = [];

    /**
     * Register a template render request and return a unique token.
     *
     * @param  array<string, mixed>  $params
     */
    public static function register(string $template, array $params = []): string
    {
        $token = bin2hex(random_bytes(16));

        self::$requests[$token] = [
            'template' => $template,
            'params' => $params,
        ];

        return $token;
    }

    /**
     * Retrieve and remove a template render request by token.
     *
     * @return array{template: string, params: array<string, mixed>}|null
     */
    public static function retrieve(string $token): ?array
    {
        if (! isset(self::$requests[$token])) {
            return null;
        }

        $request = self::$requests[$token];
        unset(self::$requests[$token]);

        return $request;
    }

    /**
     * Check if a token exists in the registry.
     */
    public static function has(string $token): bool
    {
        return isset(self::$requests[$token]);
    }

    /**
     * Clear all registered requests.
     */
    public static function clear(): void
    {
        self::$requests = [];
    }

    /**
     * The URL prefix used for template rendering requests.
     */
    public const URL_PREFIX = '/__craftpest_template/';
}
