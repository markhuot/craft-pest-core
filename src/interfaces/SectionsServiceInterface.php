<?php

namespace markhuot\craftpest\interfaces;

use craft\models\EntryType;
use craft\models\Section;

interface SectionsServiceInterface
{
    public function saveSection(Section $section): bool;

    public function saveEntryType(EntryType $entryType): bool;

    public function getSectionById(int $id): ?Section;

    public function getSectionByHandle(string $handle): ?Section;

    public function getAllEntryTypes(): array;

    public function getEntryTypeById(int $id): ?EntryType;

    public function getEntryTypeByHandle(string $handle): ?EntryType;

    public function getEntryTypesBySectionId(int $sectionId): array;
}
