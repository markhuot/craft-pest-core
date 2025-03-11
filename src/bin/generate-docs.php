<?php

foreach ([
    __DIR__.'/../../vendor/autoload.php', // if we're a top-level project/clone
    __DIR__.'/../../../vendor/autoload.php', // if we're a dependency in the vendor folder
] as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require $autoloadPath;
    }
}

$input = $argv[1] ?? null;
$output = $argv[2] ?? null;

if ($input === null || $input === '' || $input === '0' || ($output === null || $output === '' || $output === '0') || ! file_exists($input)) {
    throw new \Exception('Could not find source ['.$input.']');
}

echo "Transforming [$input] to [$output]\n";

$basename = basename($input);
$info = pathinfo($input);
$className = $info['filename'];
$namespace = str_replace('/', '\\', preg_replace('/^(.\/)?src\//', '', $info['dirname']));

require $input;
$contents = parseClass('markhuot\\craftpest\\'.$namespace.'\\'.$className);

function parseClass(string $className): array
{
    $reflection = new ReflectionClass($className);
    $classComment = $reflection->getDocComment();

    $contents = [];
    $contents[] = parseComment($classComment);

    foreach ($reflection->getMethods() as $method) {
        if ($method->getDeclaringClass()->getName() === $reflection->getName() &&
            $comment = $method->getDocComment() &&
            $method->isPublic() &&
            ! str_starts_with($method->getName(), '__') &&
            in_array(str_contains($method->getDocComment(), '@internal'), [0, false], true)
        ) {
            $comment = parseComment($method->getDocComment());
            if ($comment !== '' && $comment !== '0') {
                $params = array_map(fn (ReflectionParameter $param): string => ($param->getType() instanceof \ReflectionType ? $param->getType().' ' : ''). // @phpstan-ignore-line for some reason PHP stan doesn't like ->getName on a type
                    '$'.$param->getName().
                    ($param->isDefaultValueAvailable() ? ' = '.preg_replace('/[\r\n]+/', '', var_export($param->getDefaultValue(), true)) : ''), $method->getParameters());
                $contents[] = '## '.$method->getName().'('.implode(', ', $params).")\n".$comment;
            }
        }
    }

    return $contents;
}

function parseComment(string $comment): string
{
    preg_match_all('/@see\s+(.+)$/m', $comment, $sees);
    foreach ($sees[1] as $index => $otherClass) {
        $comment = str_replace($sees[0][$index], 'SEE['.$otherClass.']', $comment);
    }

    $comment = preg_replace('/^\/\*\*/', '', $comment);
    $comment = preg_replace('/^\s*\*\s@\w+.*$/m', '', (string) $comment);
    $comment = preg_replace('/^\s*\* ?/m', '', (string) $comment);
    $comment = preg_replace('/\n{3,}/', "\n\n", (string) $comment);
    $comment = preg_replace('/\/$/', '', (string) $comment);
    $comment = preg_replace('/(^\s+|\s+$)/', '', (string) $comment);

    preg_match_all('/^SEE\[(.+)\]$/m', (string) $comment, $sees);
    foreach ($sees[1] as $index => $otherClass) {
        $comment = str_replace($sees[0][$index], "\n\n".implode("\n\n", parseClass($otherClass))."\n\n", $comment);
    }

    return trim((string) $comment);
}

if (! is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}
file_put_contents($output, trim(implode("\n\n", $contents))."\n");
