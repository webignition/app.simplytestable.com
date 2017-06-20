<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\User;

class ModelFactory
{
    /**
     * @param array $userValues
     *
     * @return User
     */
    public static function createUser($userValues)
    {
        $user = new User();

        $user->setEmail($userValues['email']);
        $user->setEmailCanonical($userValues['email']);

        return $user;
    }
}
