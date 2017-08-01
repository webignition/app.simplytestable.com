<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserPostActivationProperties\GetForUser;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserHasNoneTest extends ServiceTest {

    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);
    }


    public function testUserPostActivationPropertiesIsNull() {
        $this->assertNull($this->userPostActivationProperties);
    }

}
