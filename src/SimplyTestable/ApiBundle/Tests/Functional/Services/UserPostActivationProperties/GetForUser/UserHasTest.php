<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserPostActivationProperties\GetForUser;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserHasTest extends ServiceTest {

    protected function setUp() {
        parent::setUp();

        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $accountPlan = $accountPlanService->find('personal');

        $this->getUserPostActivationPropertiesService()->create($user, $accountPlan);

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);
    }


    public function testUserPostActivationPropertiesIsOfCorrectType() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\UserPostActivationProperties', $this->userPostActivationProperties);
    }

}
