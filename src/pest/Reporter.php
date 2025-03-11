<?php

namespace markhuot\craftpest\pest;

use Illuminate\Support\Collection;
use SebastianBergmann\CodeCoverage\Node\File;

abstract class Reporter
{
    const IGNORE = 'ignore';

    public function __construct(protected File $file) {}

    /**
     * @return bool|string
     */
    public function canReportOn()
    {
        return true;
    }

    public function getName(): string
    {
        return $this->file->id();
    }

    public function getSourceLineFor(int $line)
    {
        return $line;
    }

    public function getLineCoverageData()
    {
        return collect($this->file->lineCoverageData())
            ->mapWithKeys(fn ($value, $key) => [$this->getSourceLineFor($key) => $value])
            ->filter(fn ($value, $key): bool => $key !== 0 && ($key !== '' && $key !== '0'));
    }

    public function getUncoveredLines(): Collection
    {
        return $this->getLineCoverageData()
            ->filter(fn ($line): bool => empty($line))
            ->keys();
    }

    public function getUncoveredLineRanges(): Collection
    {
        $ranges = [];
        $lastLineMerged = false;

        // [1 => true, 2 => true, 3 => true, 4 => false, 5 => true , 6 => true]
        // 1..3, 5, 6
        foreach ($this->getLineCoverageData() as $line => $lineCoverageData) {
            $missingCoverage = empty($lineCoverageData);

            if ($missingCoverage && $lastLineMerged) {
                $ranges[count($ranges) - 1][1] = $line;
                $lastLineMerged = true;
            } elseif ($missingCoverage) {
                $ranges[] = [$line];
                $lastLineMerged = true;
            } else {
                $lastLineMerged = false;
            }
        }

        return collect($ranges)->map(fn ($r): string => implode('..', $r))->flatten();
    }

    public function getNumberOfExecutableLines(): int
    {
        return $this->file->numberOfExecutableLines();
    }

    public function getNumberOfExecutedLines(): int
    {
        return $this->file->numberOfExecutedLines();
    }
}
