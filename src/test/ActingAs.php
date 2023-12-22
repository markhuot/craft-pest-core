<?php

namespace markhuot\craftpest\test;

use craft\web\User;
use markhuot\craftpest\factories\User as UserFactory;

/**
 * # Logging in
 *
 * You can log in users for your test by using the `->actingAs` method and it's companion `->actingAsAdmin` method.
 */
trait ActingAs
{
    protected ?string $withToken = null;

    /**
     * Acting as accepts a number of "user-like" identifiers to log in a user for the test. You may pass,
     *
     * 1. A user factory, `->actingAs(User::factory())`
     * 2. A user, `->actingAs(User::find()->id(1)->one())`
     * 3. A string that may be a username or email address, `->actingAs('my_great_username')`
     * 4. A callable that returns a User element, `->actingAs(fn () => $someUser)`
     * 5. `null` to log the user out for the given request
     */
    public function actingAs(UserFactory|User|string|callable|null $userOrName = null): self
    {
        if (is_null($userOrName)) {
            \Craft::$app->getUser()->logout(false);

            return $this;
        } elseif (is_string($userOrName)) {
            $user = \Craft::$app->getUsers()->getUserByUsernameOrEmail($userOrName);
        } elseif (is_a($userOrName, User::class)) {
            $user = $userOrName;
        } elseif (is_a($userOrName, UserFactory::class)) {
            $user = $userOrName->create();
        } elseif (is_callable($userOrName)) {
            $user = $userOrName();
        }

        if (empty($user)) {
            throw new \Exception('Unknown user `'.$userOrName.'`');
        }

        \Craft::$app->getUser()->setIdentity($user);

        return $this;
    }

    /**
     * For many tests the actual user doesn't matter, only that the user is an admin. This method
     * will return a generic user with admin permissions. This is helpful for testing that something
     * works, not whether the permissions for that thing are accurate. For more fine-tuned permission
     * testing you should use `->actingAs()` with a curated user element.
     */
    public function actingAsAdmin()
    {
        return $this->actingAs(UserFactory::factory()->admin(true)->create());
    }

    /**
     * For GQL requests (and other bearer token requests) you can set a token on the request by calling
     * `->withToken()` and passing a valid bearer token.
     *
     * ```php
     * $this->withToken($token)->get('/')->assertOk();
     * ```
     */
    public function withToken(string $token)
    {
        $this->withToken = $token;

        return $this;
    }

    public function tearDownActingAs()
    {
        \Craft::$app->getUser()->logout(false);
    }
}
