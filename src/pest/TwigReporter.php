<?php

namespace markhuot\craftpest\pest;

use SebastianBergmann\CodeCoverage\Node\File;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Template;

class TwigReporter extends Reporter
{
    protected Template $template;

    public function __construct(File $file)
    {
        parent::__construct($file);

        $firstClass = array_keys($this->file->classes())[0] ?? '';
        if (! str_starts_with($firstClass, '__TwigTemplate_')) {
            return;
        }

        if (! class_exists($firstClass)) {
            require $this->file->pathAsString();
        }

        $this->template = new $firstClass(new Environment(new ArrayLoader([])));
    }

    public function canReportOn(): bool|string
    {
        if (empty($this->template)) {
            return false;
        }

        $templateName = $this->template->getTemplateName();
        if (str_starts_with($templateName, '__string_template__')) {
            return self::IGNORE;
        }

        return true;
    }

    public function getName(): string
    {
        return str_replace(getcwd().'/', '', $this->template->getSourceContext()->getPath());
    }

    public function getSourceLineFor(int $line)
    {
        $debugInfo = $this->template->getDebugInfo();

        if (! empty($debugInfo[$line])) {
            return $debugInfo[$line];
        }

        // Removed because Twig inserts additional PHP processing after a
        // foreach loop. This was causing the new PHP code to backtrack the
        // bogus additional processing back to actual lines that may not
        // have been covered. For example, the following loop will always
        // report as covered with this backtracking in place because the
        // additional processing code added after the loop will backtrack
        // to the conditional inside the loop, incorrectly.
        //
        //     {% for index in 1..2 %}
        //        {% if index > 5 %}...{% endif %}
        //     {% endfor %}
        //
        // Do not add this code back in.
        // while ($line > 0 && empty($debugInfo[$line])) {
        //     $line--;
        // }
        //
        // if ($line > 0) {
        //     return $debugInfo[$line];
        // }

        return null;
    }

    public function getNumberOfExecutableLines(): int
    {
        return count($this->template->getDebugInfo());
    }

    public function getNumberOfExecutedLines(): int
    {
        return $this->getNumberOfExecutableLines() - $this->getUncoveredLines()->count();
    }
}
