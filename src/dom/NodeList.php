<?php

namespace markhuot\craftpest\dom;

use markhuot\craftpest\http\RequestBuilder;
use markhuot\craftpest\test\SnapshotAssertions;
use Pest\Expectation;
use PHPUnit\Framework\Assert;

/**
 * # Node list
 *
 * A `NodeList` represents a fragment of HTML. It can contain one or more nodes and
 * the return values of its methods vary based on the count. For example getting the text
 * of a single h1 element via `$response->querySelector('h1')->text === "string"` will return the string
 * contents of that node. However, if the `NodeList` contains multiple nodes the return
 * will be an array such as when you get back multiple list items, `$response->querySelector('li')->text === ["list", "text", "items"]`
 *
 * @property int $count
 */
class NodeList implements \Countable
{
    use SnapshotAssertions;

    /** @var \Symfony\Component\DomCrawler\Crawler */
    public $crawler;

    public function __construct(\Symfony\Component\DomCrawler\Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    /**
     * Further filter the NodeList to a subset of matching elements
     *
     * ```php
     * $response->querySelector('ul')->querySelector('li');
     * ```
     */
    public function querySelector(string $selector): self
    {
        return new self($this->crawler->filter($selector));
    }

    /**
     * You can turn any `NodeList` in to an expectation API by calling `->expect()` on it. From there
     * you are free to use the expectation API to assert the DOM matches your expectations.
     *
     * ```php
     * $response->querySelector('li')->expect()->count->toBe(10);
     * ```
     */
    public function expect(): \Pest\Expectation
    {
        return new Expectation($this);
    }

    /**
     * Allows access to the getText() and getInnerHTML() methods via magic properties
     * so a NodeList can be expected.
     *
     * ```php
     * expect($nodeList)->text->toBe('some text content');
     * ```
     *
     * @internal
     */
    public function __get($property)
    {
        $getter = 'get'.ucfirst((string) $property);

        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        throw new \Exception("Property `{$property}` not found on Pest\\CraftCms\\NodeList");
    }

    /**
     * A poorly named map that either returns the result of the map on
     * a single node or an array of mapped values on multiple nodes.
     *
     * This is called internally when you `__get` on a node list.
     *
     * If `$nodeList` contains 1 node, you'll get back the text content
     * of that node.
     * ```php
     * $textContent = $nodeList->getNodeOrNodes(fn ($node) => $node->text()); // string
     * ```
     *
     * If `$nodeList` contains 2 or more nodes, you'll get back an array
     * containing the text content of each node.
     * ```php
     * $textContent = $nodeList->getNodeOrNodes(fn ($node) => $node->text()); // array
     * ```
     */
    public function getNodeOrNodes(callable $callback)
    {
        $count = $this->crawler->count();
        $results = $this->each($callback);

        return $count <= 1 ? ($results[0] ?? null) : $results;
    }

    /**
     * Loop over each matched node and apply the callback to the node. Returns
     * an array of results for each matched node.
     * @return mixed[]
     */
    public function each(callable $callback): array
    {
        $result = [];

        for ($i = 0; $i < $this->crawler->count(); $i++) {
            $node = $this->crawler->eq($i);
            $result[] = $callback($node);
        }

        return $result;
    }

    /**
     * Available as a method or a magic property of `->text`. Gets the text content of the node or nodes. This
     * will only return the text content of the node as well as any child nodes. Any non-text content such as
     * HTML tags will be removed.
     */
    public function getText(): array|string
    {
        return $this->getNodeOrNodes(fn ($node) => $node->text());
    }

    /**
     * Available as a method or a magic property of `->innerHTML`. Gets the inner HTML of the node or nodes.
     */
    public function getInnerHTML(): array|string
    {
        return $this->getNodeOrNodes(fn ($node) => $node->html());
    }

    /**
     * The number of nodes within the node list. Used for `\Countable` purposes. Most
     * access would be through the `getCount()` method.
     *
     * @internal
     */
    public function count(): int
    {
        return $this->crawler->count();
    }

    /**
     * Available via the method or a magic property of `->count` returns
     * the number of nodes in the node list.
     */
    public function getCount(): int
    {
        return $this->count();
    }

    /**
     * Click the matched element and follow a link.
     *
     * ```php
     * $response->querySelector('a')->click();
     * ```
     */
    public function click(): \markhuot\craftpest\web\TestableResponse
    {
        $node = $this->crawler->first();
        $nodeName = $node->nodeName();

        if ($nodeName === 'a') {
            $href = $node->attr('href');

            return (new RequestBuilder('get', $href))->send();
        }

        throw new \Exception('Not able to interact with `'.$nodeName.'` elements.');
    }

    /**
     * Assert all matched nodes have the given attribute. If you have matched multiple nodes
     * all nodes must matched.
     *
     * ```php
     * $response->querySelector('form')->assertAttribute('method', 'post');
     * ```
     */
    public function assertAttribute(string $key, string $value): static
    {
        if ($this->crawler->count() === 0) {
            Assert::fail('No matching elements to assert against attribute `'.$key.'`');
        }

        $this->each(function ($node) use ($key, $value): void {
            $keys = [];
            foreach ($node->getNode(0)->attributes as $attr) {
                $keys[] = $attr->name;
            }
            Assert::assertContains($key, $keys);
            Assert::assertSame($value, $node->attr($key));
        });

        return $this;
    }

    /**
     * Asserts that the given string matches the text content of the node list.
     *
     * Caution: if the node list contains multiple nodes then the assertion
     * would expect an array of strings to match.
     *
     * ```php
     * $nodeList->assertText('Hello World');
     * ```
     */
    public function assertText($expected): static
    {
        Assert::assertSame($expected, $this->getText());

        return $this;
    }

    /**
     * Asserts that the given string is a part of the node list text content
     *
     * ```php
     * $nodeList->assertContainsString('Hello');
     * ```
     */
    public function assertContainsString(string $expected): static
    {
        Assert::assertStringContainsString($expected, $this->getText());

        return $this;
    }

    /**
     * Asserts that the given count matches the count of nodes in the node list.
     *
     * ```php
     * $nodeList->assertCount(2);
     * ```
     */
    public function assertCount(int $expected): static
    {
        Assert::assertCount($expected, $this);

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $result = [];

        for ($i = 0; $i < $this->crawler->count(); $i++) {
            $node = $this->crawler->eq($i);
            $result[] = $node->outerHtml();
        }

        return $result;
    }
}
