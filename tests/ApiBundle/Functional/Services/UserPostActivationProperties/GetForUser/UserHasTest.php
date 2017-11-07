<?php

namespace Tests\ApiBundle\Functional\Services\UserPostActivationProperties\GetForUser;

use Tests\ApiBundle\Factory\UserFactory;

class UserHasTest extends ServiceTest {

    protected function setUp() {
        parent::setUp();

        $accountPlanService = $this->container->get('simplytestable.services.accountplan');

        $plan = $accountPlanService->get('personal');

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();

        $this->getUserPostActivationPropertiesService()->create($user, $plan);

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);
    }


    public function testUserPostActivationPropertiesIsOfCorrectType() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\UserPostActivationProperties', $this->userPostActivationProperties);
    }

}
