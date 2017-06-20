<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserPostActivationProperties\GetForUser;

class UserHasNoneTest extends ServiceTest {

    public function setUp() {
        parent::setUp();

        $user = $this->createAndFindUser('user@example.com');
        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);
    }


    public function testUserPostActivationPropertiesIsNull() {
        $this->assertNull($this->userPostActivationProperties);
    }

}
