<?php

namespace Tests\ApiBundle\Functional\Services\UserPostActivationProperties\GetForUser;

use Tests\ApiBundle\Factory\UserFactory;

class UserHasNoneTest extends ServiceTest {

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);
    }


    public function testUserPostActivationPropertiesIsNull() {
        $this->assertNull($this->userPostActivationProperties);
    }

}
