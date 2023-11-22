<?php

namespace markhuot\craftpest\factories;

use craft\base\ElementInterface;
use craft\helpers\StringHelper;
use craft\models\Section_SiteSettings;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;
use function markhuot\craftpest\helpers\base\array_wrap;

/**
 * @method self name(string $name)
 * @method self handle(string $name)
 * @method self type(string $type)
 * @method \craft\models\Section|Collection<\craft\models\Section> create(array $definition = [])
 */
class Section extends Factory {

    use Fieldable;

    protected $hasUrls = true;

    protected $uriFormat = '{slug}';

    protected $enabledByDefault = true;

    protected $template = '_{handle}/entry';

    function hasUrls(bool $hasUrls)
    {
        $this->hasUrls = $hasUrls;

        return $this;
    }

    function uriFormat(string $uriFormat)
    {
        $this->uriFormat = $uriFormat;

        return $this;
    }

    function enabledByDefault(bool $enabledByDefault)
    {
        $this->enabledByDefault = $enabledByDefault;

        return $this;
    }

    function template(string $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get the element to be generated
     *
     * @return \craft\models\Section
     */
    function newElement()
    {
        return new \craft\models\Section();
    }

    /**
     * The faker definition
     *
     * @return array
     */
    function definition(int $index = 0) {
        $name = $this->faker->words(2, true);

        return [
            'name' => $name,
            'type' => 'channel',
        ];
    }

    public function inferences(array $definition = [])
    {
        if (! empty($definition['name']) && empty($definition['handle'])) {
            $definition['handle'] = StringHelper::toCamelCase($definition['name']);
        }

        $name = $definition['name'];
        $handle = $definition['handle'];
        $definition['siteSettings'] = collect(\Craft::$app->sites->getAllSites())
            ->mapWithkeys(function ($site) use ($name, $handle) {
                $settings = new Section_SiteSettings();
                $settings->siteId = $site->id;
                $settings->hasUrls = $this->hasUrls;
                $settings->uriFormat = $this->uriFormat;
                $settings->enabledByDefault = $this->enabledByDefault;
                $settings->template = \Craft::$app->view->renderObjectTemplate($this->template, [
                    'name' => $name,
                    'handle' => $handle
                ]);

                return [$site->id => $settings];
            })->toArray();

        return $definition;
    }

    /**
     * Persist the entry to local
     *
     * @param \craft\models\Section $element
     */
    function store($element) {
        $result = \Craft::$app->sections->saveSection($element);
        $this->storeFields($element->entryTypes[0]->fieldLayout);

        return $result;
    }

}
