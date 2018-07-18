<?php

namespace Tests\AppBundle\Unit\Entity;

use AppBundle\Entity\User;

class UserTest extends \PHPUnit\Framework\TestCase
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
