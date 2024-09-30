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

if (empty($input) || empty($output) || ! file_exists($input)) {
    throw new \Exception('Could not find source ['.$input.']');
}

echo "Transforming [$input] to [$output]\n";

$basename = basename($input);
$info = pathinfo($input);
$className = $info['filename'];
$namespace = str_replace('/', '\\', preg_replace('/^(.\/)?src\//', '', $info['dirname']));

require $input;
$contents = parseClass('markhuot\\craftpest\\'.$namespace.'\\'.$className);

function parseClass(string $className)
{
    $reflection = new ReflectionClass($className);
    $classComment = $reflection->getDocComment();

    $contents = [];
    $contents[] = parseComment($classComment);

    foreach ($reflection->getMethods() as $method) {
        if ($method->getDeclaringClass()->getName() === $reflection->getName() &&
            $comment = $method->getDocComment() &&
            $method->isPublic() &&
            substr($method->getName(), 0, 2) !== '__' &&
            strpos($method->getDocComment(), '@internal') === false
        ) {
            $comment = parseComment($method->getDocComment());
            if (! empty($comment)) {
                $params = array_map(function (ReflectionParameter $param) {
                    return ($param->getType() ? (string) $param->getType().' ' : ''). // @phpstan-ignore-line for some reason PHP stan doesn't like ->getName on a type
                        '$'.$param->getName().
                        ($param->isDefaultValueAvailable() ? ' = '.preg_replace('/[\r\n]+/', '', var_export($param->getDefaultValue(), true)) : '');
                }, $method->getParameters());
                $contents[] = '## '.$method->getName().'('.implode(', ', $params).")\n".$comment;
            }
        }
    }

    return $contents;
}

function parseComment(string $comment)
{
    preg_match_all('/@see\s+(.+)$/m', $comment, $sees);
    foreach ($sees[1] as $index => $otherClass) {
        $comment = str_replace($sees[0][$index], 'SEE['.$otherClass.']', $comment);
    }

    $comment = preg_replace('/^\/\*\*/', '', $comment);
    $comment = preg_replace('/^\s*\*\s@\w+.*$/m', '', $comment);
    $comment = preg_replace('/^\s*\* ?/m', '', $comment);
    $comment = preg_replace('/\n{3,}/', "\n\n", $comment);
    $comment = preg_replace('/\/$/', '', $comment);
    $comment = preg_replace('/(^\s+|\s+$)/', '', $comment);

    preg_match_all('/^SEE\[(.+)\]$/m', $comment, $sees);
    foreach ($sees[1] as $index => $otherClass) {
        $comment = str_replace($sees[0][$index], "\n\n".implode("\n\n", parseClass($otherClass))."\n\n", $comment);
    }

    return trim($comment);
}

if (! is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}
file_put_contents($output, trim(implode("\n\n", $contents))."\n");
