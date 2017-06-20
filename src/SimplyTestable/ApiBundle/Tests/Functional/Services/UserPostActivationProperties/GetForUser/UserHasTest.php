<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\UserPostActivationProperties\GetForUser;

class UserHasTest extends ServiceTest {

    public function setUp() {
        parent::setUp();

        $user = $this->createAndFindUser('user@example.com');
        $accountPlan = $this->getAccountPlanService()->find('personal');

        $this->getUserPostActivationPropertiesService()->create($user, $accountPlan);

        $this->userPostActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);
    }


    public function testUserPostActivationPropertiesIsOfCorrectType() {
        $this->assertInstanceOf('SimplyTestable\ApiBundle\Entity\UserPostActivationProperties', $this->userPostActivationProperties);
    }

}
