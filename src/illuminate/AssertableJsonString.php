<?php

namespace markhuot\craftpest\illuminate;

use ArrayAccess;
use Closure;
use Countable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;
use markhuot\craftpest\illuminate\Assert as PHPUnit;

class AssertableJsonString implements ArrayAccess, Countable
{
    /**
     * The decoded json contents.
     *
     * @var array|null
     */
    protected $decoded;

    /**
     * Create a new assertable JSON string instance.
     *
     * @param \Illuminate\Contracts\Support\Jsonable|\JsonSerializable|array|string $json
     * @return void
     */
    public function __construct(/**
     * The original encoded json.
     */
    public $json)
    {
        if ($this->json instanceof JsonSerializable) {
            $this->decoded = $this->json->jsonSerialize();
        } elseif ($this->json instanceof Jsonable) {
            $this->decoded = json_decode($this->json->toJson(), true);
        } elseif (is_array($this->json)) {
            $this->decoded = $this->json;
        } else {
            $this->decoded = json_decode($this->json, true);
        }
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function json($key = null)
    {
        return data_get($this->decoded, $key);
    }

    /**
     * Assert that the response JSON has the expected count of items at the given key.
     *
     * @param  string|null  $key
     * @return $this
     */
    public function assertCount(int $count, $key = null): static
    {
        if (! is_null($key)) {
            PHPUnit::assertCount(
                $count, data_get($this->decoded, $key),
                "Failed to assert that the response count matched the expected {$count}"
            );

            return $this;
        }

        PHPUnit::assertCount($count,
            $this->decoded,
            "Failed to assert that the response count matched the expected {$count}"
        );

        return $this;
    }

    /**
     * Assert that the response has the exact given JSON.
     *
     * @return $this
     */
    public function assertExact(array $data): static
    {
        $actual = $this->reorderAssocKeys((array) $this->decoded);

        $expected = $this->reorderAssocKeys($data);

        PHPUnit::assertEquals(
            json_encode($expected, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $this;
    }

    /**
     * Assert that the response has the similar JSON as given.
     *
     * @return $this
     */
    public function assertSimilar(array $data): static
    {
        $actual = json_encode(
            Arr::sortRecursive((array) $this->decoded),
            JSON_UNESCAPED_UNICODE
        );

        PHPUnit::assertEquals(json_encode(Arr::sortRecursive($data), JSON_UNESCAPED_UNICODE), $actual);

        return $this;
    }

    /**
     * Assert that the response contains the given JSON fragment.
     *
     * @return $this
     */
    public function assertFragment(array $data): static
    {
        $actual = json_encode(
            Arr::sortRecursive((array) $this->decoded),
            JSON_UNESCAPED_UNICODE
        );

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = $this->jsonSearchStrings($key, $value);

            PHPUnit::assertTrue(
                Str::contains($actual, $expected),
                'Unable to find JSON fragment: '.PHP_EOL.PHP_EOL.
                '['.json_encode([$key => $value], JSON_UNESCAPED_UNICODE).']'.PHP_EOL.PHP_EOL.
                'within'.PHP_EOL.PHP_EOL.
                "[{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the response does not contain the given JSON fragment.
     *
     * @param  bool  $exact
     * @return $this
     */
    public function assertMissing(array $data, $exact = false): static
    {
        if ($exact) {
            return $this->assertMissingExact($data);
        }

        $actual = json_encode(
            Arr::sortRecursive((array) $this->decoded),
            JSON_UNESCAPED_UNICODE
        );

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $unexpected = $this->jsonSearchStrings($key, $value);

            PHPUnit::assertFalse(
                Str::contains($actual, $unexpected),
                'Found unexpected JSON fragment: '.PHP_EOL.PHP_EOL.
                '['.json_encode([$key => $value], JSON_UNESCAPED_UNICODE).']'.PHP_EOL.PHP_EOL.
                'within'.PHP_EOL.PHP_EOL.
                "[{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Assert that the response does not contain the exact JSON fragment.
     *
     * @return $this
     */
    public function assertMissingExact(array $data): static
    {
        $actual = json_encode(
            Arr::sortRecursive((array) $this->decoded),
            JSON_UNESCAPED_UNICODE
        );

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $unexpected = $this->jsonSearchStrings($key, $value);

            if (! Str::contains($actual, $unexpected)) {
                return $this;
            }
        }

        PHPUnit::fail(
            'Found unexpected JSON fragment: '.PHP_EOL.PHP_EOL.
            '['.json_encode($data, JSON_UNESCAPED_UNICODE).']'.PHP_EOL.PHP_EOL.
            'within'.PHP_EOL.PHP_EOL.
            "[{$actual}]."
        );

        return $this;
    }

    /**
     * Assert that the response does not contain the given path.
     *
     * @param  string  $path
     * @return $this
     */
    public function assertMissingPath($path): static
    {
        PHPUnit::assertFalse(Arr::has($this->json(), $path));

        return $this;
    }

    /**
     * Assert that the expected value and type exists at the given path in the response.
     *
     * @param  string  $path
     * @return $this
     */
    public function assertPath($path, mixed $expect): static
    {
        if ($expect instanceof Closure) {
            PHPUnit::assertTrue($expect($this->json($path)));
        } else {
            PHPUnit::assertSame($expect, $this->json($path));
        }

        return $this;
    }

    /**
     * Assert that the response has a given JSON structure.
     *
     * @param  array|null  $responseData
     * @return $this
     */
    public function assertStructure(?array $structure = null, $responseData = null)
    {
        if (is_null($structure)) {
            return $this->assertSimilar($this->decoded);
        }

        if (! is_null($responseData)) {
            return (new static($responseData))->assertStructure($structure);
        }

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                PHPUnit::assertIsArray($this->decoded);

                foreach ($this->decoded as $responseDataItem) {
                    $this->assertStructure($structure['*'], $responseDataItem);
                }
            } elseif (is_array($value)) {
                PHPUnit::assertArrayHasKey($key, $this->decoded);

                $this->assertStructure($structure[$key], $this->decoded[$key]);
            } else {
                PHPUnit::assertArrayHasKey($value, $this->decoded);
            }
        }

        return $this;
    }

    /**
     * Assert that the response is a superset of the given JSON.
     *
     * @param  bool  $strict
     * @return $this
     */
    public function assertSubset(array $data, $strict = false): static
    {
        PHPUnit::assertArraySubset(
            $data, $this->decoded, $strict, $this->assertJsonMessage($data)
        );

        return $this;
    }

    /**
     * Reorder associative array keys to make it easy to compare arrays.
     *
     * @return array
     */
    protected function reorderAssocKeys(array $data)
    {
        $data = Arr::dot($data);
        ksort($data);

        $result = [];

        foreach ($data as $key => $value) {
            Arr::set($result, $key, $value);
        }

        return $result;
    }

    /**
     * Get the assertion message for assertJson.
     */
    protected function assertJsonMessage(array $data): string
    {
        $expected = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $actual = json_encode($this->decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return 'Unable to find JSON: '.PHP_EOL.PHP_EOL.
            "[{$expected}]".PHP_EOL.PHP_EOL.
            'within response JSON:'.PHP_EOL.PHP_EOL.
            "[{$actual}].".PHP_EOL.PHP_EOL;
    }

    /**
     * Get the strings we need to search for when examining the JSON.
     *
     * @param  string  $key
     * @param  string  $value
     */
    protected function jsonSearchStrings($key, $value): array
    {
        $needle = Str::substr(json_encode([$key => $value], JSON_UNESCAPED_UNICODE), 1, -1);

        return [
            $needle.']',
            $needle.'}',
            $needle.',',
        ];
    }

    /**
     * Get the total number of items in the underlying JSON array.
     */
    public function count(): int
    {
        return count($this->decoded);
    }

    /**
     * Determine whether an offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->decoded[$offset]);
    }

    /**
     * Get the value at the given offset.
     *
     * @param  string  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->decoded[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->decoded[$offset] = $value;
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->decoded[$offset]);
    }
}
