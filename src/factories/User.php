<?php

namespace markhuot\craftpest\factories;

/**
 * @method self admin(bool $isAdmin)
 */
class User extends Element
{
    public function newElement()
    {
        return new \craft\elements\User;
    }

    public function definition(int $index = 0)
    {
        $email = $this->faker->safeEmail();

        return [
            'email' => $email,
            'username' => $email,
        ];
    }
}
