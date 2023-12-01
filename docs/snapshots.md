# Snapshots
A variety of snapshot assertions are available to help you test your HTML and DOM output in craft-pest. In
many places you can simply expect an object `->toMatchSnapshot()` and Pest will handle the rest for you.

The two entrypoints to snapshotting are,

- `$this->assertMatchesSnapshot()`
- `expect($object)->toMatchSnapshot()`

For example, responses, DOM Lists, views, and Entries are all snapshotable.

```php
it('matches responses')->get('/')->assertMatchesSnapshot();
it('matches dom lists')->get('/')->querySelector('h1')->assertMatchesSnapshot();
it('matches views')->renderTemplate('_news/entry', $variables);
it('matches entries', function () { Entry::find()->one()->assertMatchesSnapshot(); });
```

## Attributes

By default, only a subset of attributes from Entries are snapshotted. This way dynamic fields
that may change test to test do not create unexpected failures. For example, the
`$entry->postDate` defaults to the current time when the entry was generated. That means
running the test on Tuesday and again on Wednesday would fail the test because the `postDate`
would be Tuesday in the initial snapshot and Wednesday during the comparison.

If you'd like to include additional attributes in the snapshot you can pass them as an array
to the `->toSnapshot()` method. For example,

```php
it('snapshots postDate', function () {
    $entry = Entry::factory()->postDate('2022-01-01')->create();

    expect($entry->toSnapshot(['postDate']))->toMatchSnapshot();
    $entry->assertMatchesSnapshot(['postDate']);
});
