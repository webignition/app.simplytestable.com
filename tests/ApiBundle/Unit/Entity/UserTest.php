<?php

namespace Tests\ApiBundle\Unit\Entity;

use SimplyTestable\ApiBundle\Entity\User;

class UserTest extends \PHPUnit_Framework_TestCase
{
    public function testJsonSerialize()
    {
        $email = 'user@example.com';

        $user = new User();
        $user->setEmailCanonical('user@example.com');

        $this->assertEquals([
            'email' => $email,
        ], $user->jsonSerialize());
    }
}
