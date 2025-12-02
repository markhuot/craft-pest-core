<?php

namespace markhuot\craftpest\browser;

/**
 * Configuration for visitTemplate() layout settings.
 *
 * Stores the default layout template and block name that will be used
 * when rendering templates via visitTemplate().
 */
class VisitTemplateConfig
{
    /**
     * The default layout template path.
     */
    private static ?string $defaultLayout = null;

    /**
     * The default block name where the template content will be rendered.
     */
    private static string $defaultBlock = 'content';

    /**
     * Set the default layout for visitTemplate() calls.
     *
     * Call this in your Pest.php file to set a global default:
     *
     * ```php
     * useDefaultVisitTemplateLayout('_layouts/base', 'content');
     * ```
     */
    public static function setDefaultLayout(?string $layout, string $block = 'content'): void
    {
        self::$defaultLayout = $layout;
        self::$defaultBlock = $block;
    }

    /**
     * Get the default layout template path.
     */
    public static function getDefaultLayout(): ?string
    {
        return self::$defaultLayout;
    }

    /**
     * Get the default block name.
     */
    public static function getDefaultBlock(): string
    {
        return self::$defaultBlock;
    }

    /**
     * Reset configuration to defaults (useful for testing).
     */
    public static function reset(): void
    {
        self::$defaultLayout = null;
        self::$defaultBlock = 'content';
    }
}
