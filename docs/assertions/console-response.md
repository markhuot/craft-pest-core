# Console Response Assertions

A testable response is returned when running a console command action. This class provides a fluent interface for
asserting on the response.

## assertSuccesful()
Assert that the console command exited successfully (with a zero exit code).

```php
$this->command(ConsoleController::class, 'actionName')->assertSuccessful();
```

## assertFailed()
Assert that the console command failed (with a non-zero exit code).

```php
$this->command(ConsoleController::class, 'actionName')->assertFailed();
```

## assertExitCode(int $exitCode)
Assert the integer exit code

```php
$this->command(ConsoleController::class, 'actionName')->assertExitCode(1337);
```

## assertSee(string $text)
Assert that the command contains the passed text in stdout or stderr

```php
$this->command(ConsoleController::class, 'actionName')->assertSee('text output');
```

## assertDontSee(string $text)
Assert that the command does not contain the passed text in stdout or stderr

```php
$this->command(ConsoleController::class, 'actionName')->assertDontSee('text output');
```
