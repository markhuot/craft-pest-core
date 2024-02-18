<?php

namespace markhuot\craftpest\factories;

use craft\models\EntryType;
use markhuot\craftpest\factories\Section as FactoriesSection;
use markhuot\craftpest\interfaces\SectionsServiceInterface;
use markhuot\craftpest\storage\FactoryFields;

use function markhuot\craftpest\helpers\base\service;

/**
 * Entry Factory
 *
 * You can easily build entries using the Entry factory.
 *
 * @TODO a lot of these should be copied up to the element factory
 *
 * @method title(string $title)
 * @method slug(string $slug)
 * @method uri(string $uri)
 * @method enabled(bool $enabled)
 * @method parent(\craft\elements\Entry|Entry|string|int $parent)
 *
 * @phpstan-ignore-next-line ignored because the file is generated
 *
 * @mixin FactoryFields
 */
class Entry extends Element
{
    /** @var string|\craft\models\Section|null */
    protected $sectionIdentifier = null;

    /** @var EntryType|string|null */
    protected $entryTypeIdentifier = null;

    protected $priorityAttributes = ['sectionId', 'typeId'];

    /**
     * Set the section for the entry to be created. You may pass a section
     * in three ways,
     *
     * 1. a section object (typically after creating one via the `Section` factory)
     * 2. a section id
     * 3. a section handle
     *
     * If you do not pass a section, one will be created automatically.
     */
    public function section($identifier)
    {
        $this->sectionIdentifier = $identifier;

        return $this;
    }

    /**
     * Set the entry type
     */
    public function type($identifier)
    {
        $this->entryTypeIdentifier = $identifier;

        return $this;
    }

    /**
     * Set the post date by passing a `DateTime`, a string representing the date like
     * "2022-04-25 04:00:00", or a unix timestamp as an integer.
     */
    public function postDate(\DateTime|string|int $value)
    {
        $this->setDateField('postDate', $value);

        return $this;
    }

    /**
     * Set the expiration date by passing a `DateTime`, a string representing the date like
     * "2022-04-25 04:00:00", or a unix timestamp as an integer.
     */
    public function expiryDate(\DateTime|string|int $value)
    {
        $this->setDateField('expiryDate', $value);

        return $this;
    }

    /**
     * Date fields in Craft require a `DateTime` object.  You can use `->setDateField` to pass
     * in other representations such as a timestamp or a string.
     *
     * ```php
     * Entry::factory()->setDateField('approvedOn', '2022-04-18 -04:00:00');
     * Entry::factory()->setDateField('approvedOn', 1665864918);
     * ```
     */
    public function setDateField($key, $value)
    {
        if (is_numeric($value)) {
            $value = new \DateTime('@'.$value);
        } elseif (is_string($value)) {
            $value = new \DateTime($value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * Set the author of the entry. You may pass a full user object, a user ID,
     * a username, email, or a user ID.
     */
    public function author(\craft\web\User|string|int $user)
    {
        if (is_numeric($user)) {
            $user = \Craft::$app->users->getUserById($user);
        } elseif (is_string($user)) {
            $user = \Craft::$app->users->getUserByUsernameOrEmail($user);
        }

        if (! is_a($user, \craft\elements\User::class)) {
            throw new \Exception('You must pass a User object or a valid user ID or username to the `author()` method.');
        }

        $this->attributes['authorId'] = $user->id;

        return $this;
    }

    /**
     * Infer the section based on the class name
     *
     * @internal
     */
    public function inferSectionId()
    {
        if (is_a($this->sectionIdentifier, \craft\models\Section::class)) {
            $section = $this->sectionIdentifier;
        } elseif (is_numeric($this->sectionIdentifier)) {
            $section = service(SectionsServiceInterface::class)->getSectionById($this->sectionIdentifier);
        } elseif (is_string($this->sectionIdentifier)) {
            $section = service(SectionsServiceInterface::class)->getSectionByHandle($this->sectionIdentifier);
        } else {
            $reflector = new \ReflectionClass($this);
            $className = $reflector->getShortName();
            $sectionHandle = lcfirst($className);
            $section = service(SectionsServiceInterface::class)->getSectionByHandle($sectionHandle);
        }

        if (empty($section)) {
            $section = Section::factory()->create();
        }

        return $section?->id;
    }

    /**
     * Infer the type based on the class name
     *
     * @internal
     */
    public function inferTypeId(?int $sectionid): int
    {
        $entryTypes = collect();
        if ($sectionid) {
            $section = service(SectionsServiceInterface::class)->getSectionById($sectionid);
            $entryTypes = collect($section->getEntryTypes());
        }

        if (is_a($this->entryTypeIdentifier, \craft\models\EntryType::class)) {
            $entryType = $this->entryTypeIdentifier;
        } elseif (is_numeric($this->entryTypeIdentifier)) {
            $entryType = $entryTypes->where('id', $this->entryTypeIdentifier)->first();
        } elseif (is_string($this->entryTypeIdentifier)) {
            $entryType = $entryTypes->where('handle', $this->entryTypeIdentifier)->first();
        } else {
            $reflector = new \ReflectionClass($this);
            $className = $reflector->getShortName();
            $typeHandle = lcfirst($className);
            $entryType = $entryTypes->where('handle', $typeHandle)->first() ??
                         $entryTypes->where('handle', 'default') ??
                         $entryTypes->first();
        }

        return $entryType?->id;
    }

    /**
     * Get the element to be generated
     *
     * @internal
     */
    public function newElement()
    {
        return new \craft\elements\Entry();
    }

    /**
     * @internal
     */
    public function inferences(array $definition = [])
    {
        $sectionId = $this->inferSectionId();
        $typeId = $this->inferTypeId($sectionId);

        return array_merge($definition, [
            'sectionId' => $sectionId,
            'typeId' => $typeId,
        ]);
    }

    public function toArray()
    {
        return [
            'type' => $this->entryTypeIdentifier,
            'enabled' => true,
            'fields' => $this->attributes,
        ];
    }
}
