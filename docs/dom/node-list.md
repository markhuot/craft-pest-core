# Node list

A `NodeList` represents a fragment of HTML. It can contain one or more nodes and
the return values of its methods vary based on the count. For example getting the text
of a single h1 element via `$response->querySelector('h1')->text === "string"` will return the string
contents of that node. However, if the `NodeList` contains multiple nodes the return
will be an array such as when you get back multiple list items, `$response->querySelector('li')->text === ["list", "text", "items"]`

## querySelector(string $selector)
Further filter the NodeList to a subset of matching elements

```php
$response->querySelector('ul')->querySelector('li');
```

## expect()
You can turn any `NodeList` in to an expectation API by calling `->expect()` on it. From there
you are free to use the expectation API to assert the DOM matches your expectations.

```php
$response->querySelector('li')->expect()->count->toBe(10);
```

## getNodeOrNodes(callable $callback)
A poorly named map that either returns the result of the map on
a single node or an array of mapped values on multiple nodes.

This is called internally when you `__get` on a node list.

If `$nodeList` contains 1 node, you'll get back the text content
of that node.
```php
$textContent = $nodeList->getNodeOrNodes(fn ($node) => $node->text()); // string
```

If `$nodeList` contains 2 or more nodes, you'll get back an array
containing the text content of each node.
```php
$textContent = $nodeList->getNodeOrNodes(fn ($node) => $node->text()); // array
```

## each(callable $callback)
Loop over each matched node and apply the callback to the node. Returns
an array of results for each matched node.

## getText()
Available as a method or a magic property of `->text`. Gets the text content of the node or nodes. This
will only return the text content of the node as well as any child nodes. Any non-text content such as
HTML tags will be removed.

Returns a string for single nodes or an array of strings for multiple nodes.

## getTextContent()
Get the text content of the node list, flattening all nodes to a single string. Unlike `getText()`, this
method always returns a string, even when multiple nodes are matched. All text from all matched nodes
is concatenated together.

```php
// Single node returns text
$text = $response->querySelector('h1')->getTextContent(); // "Hello"

// Multiple nodes returns concatenated text
$text = $response->querySelector('li')->getTextContent(); // "onetwothree"
```

## getInnerHTML()
Available as a method or a magic property of `->innerHTML`. Gets the inner HTML of the node or nodes.

## getCount()
Available via the method or a magic property of `->count` returns
the number of nodes in the node list.

## click()
Click the matched element and follow a link.

```php
$response->querySelector('a')->click();
```

## assertAttribute(string $key, string $value)
Assert all matched nodes have the given attribute. If you have matched multiple nodes
all nodes must matched.

```php
$response->querySelector('form')->assertAttribute('method', 'post');
```

## assertText($expected)
Asserts that the given string matches the text content of the node list.

Caution: if the node list contains multiple nodes then the assertion
would expect an array of strings to match.

```php
$nodeList->assertText('Hello World'); // single node
$nodeList->assertText(['one', 'two', 'three']); // multiple nodes
```

## assertTextContent(string $expected)
Asserts that the given string exactly matches the flattened text content of all nodes.
Unlike `assertText()` which expects an array for multiple nodes, this method always
compares against the concatenated string of all nodes.

```php
// Single node
$response->querySelector('h1')->assertTextContent('Hello World');

// Multiple nodes - compares against flattened string
$response->querySelector('li')->assertTextContent('onetwothree');
```

## assertContainsString($expected)
Asserts that the given string is a part of the node list text content. When multiple nodes are matched,
the text content is flattened into a single string before checking.

```php
$nodeList->assertContainsString('Hello');
```

## assertSee(string $expected)
Asserts that the node list contains the given string in its text content. This is an alias for
`assertContainsString()`. When multiple nodes are matched, the text content is flattened into a single
string before checking.

```php
$response->querySelector('li')->assertSee('two'); // passes for ["one", "two", "three"]
```

## assertCount($expected)
Asserts that the given count matches the count of nodes in the node list.

```php
$nodeList->assertCount(2);
```
