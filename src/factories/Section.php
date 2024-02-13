<?php

namespace markhuot\craftpest\factories;

use Craft;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\Section_SiteSettings;
use Faker\Factory as Faker;
use Illuminate\Support\Collection;

/**
 * @method self name(string $name)
 * @method self handle(string $name)
 * @method self type(string $type)
 * @method \craft\models\Section|Collection<\craft\models\Section> create(array $definition = [])
 */
class Section extends Factory
{
    use Fieldable;

    protected $hasUrls = true;

    protected $uriFormat = '{slug}';

    protected $enabledByDefault = true;

    protected $template = '_{handle}/entry';

    public function hasUrls(bool $hasUrls)
    {
        $this->hasUrls = $hasUrls;

        return $this;
    }

    public function uriFormat(string $uriFormat)
    {
        $this->uriFormat = $uriFormat;

        return $this;
    }

    public function enabledByDefault(bool $enabledByDefault)
    {
        $this->enabledByDefault = $enabledByDefault;

        return $this;
    }

    public function template(string $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get the element to be generated
     *
     * @return \craft\models\Section
     */
    public function newElement()
    {
        return new \craft\models\Section();
    }

    /**
     * The faker definition
     *
     * @return array
     */
    public function definition(int $index = 0)
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => $name,
            'type' => \craft\models\Section::TYPE_CHANNEL,
        ];
    }

    public function inferences(array $definition = [])
    {
        if (! empty($definition['name']) && empty($definition['handle'])) {
            $definition['handle'] = StringHelper::toCamelCase($definition['name']);
        }

        $name = $definition['name'];
        $handle = $definition['handle'];
        $definition['siteSettings'] = collect(Craft::$app->sites->getAllSites())
            ->mapWithkeys(function ($site) use ($name, $handle) {
                $settings = new Section_SiteSettings();
                $settings->siteId = $site->id;
                $settings->hasUrls = $this->hasUrls;
                $settings->uriFormat = $this->uriFormat;
                $settings->enabledByDefault = $this->enabledByDefault;
                $settings->template = Craft::$app->view->renderObjectTemplate($this->template, [
                    'name' => $name,
                    'handle' => $handle,
                ]);

                return [$site->id => $settings];
            })->toArray();

        if (empty($definition['entryTypes'])) {
            $entryType = new EntryType([
                'name' => $name = $this->faker->words(3, true),
                'handle' => StringHelper::toHandle($name),
            ]);
            Craft::$app->getEntries()->saveEntryType($entryType);
            throw_if($entryType->errors, 'Problem saving entry type: ' . implode(', ', $entryType->getFirstErrors()));
            $definition['entryTypes'] = [$entryType];
        }

        return $definition;
    }

    /**
     * Persist the entry to local
     *
     * @param  \craft\models\Section  $element
     */
    public function store($element)
    {
        $result = Craft::$app->getEntries()->saveSection($element);
        throw_unless(empty($element->errors), 'Problem saving section: ' . implode(', ', $element->getFirstErrors()));

        $this->storeFields($element->entryTypes[0]->fieldLayout);

        return $result;
    }
}
