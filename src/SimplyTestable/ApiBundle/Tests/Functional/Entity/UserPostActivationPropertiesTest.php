<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;

class UserPostActivationPropertiesTest extends BaseSimplyTestableTestCase {

    public function testPersistWithoutCoupon() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $user = $userService->create('user@example.com', 'password');
        $plan = $this->createAccountPlan();

        $userPostActivationProperties = new UserPostActivationProperties();
        $userPostActivationProperties->setUser($user);
        $userPostActivationProperties->setAccountPlan($plan);

        $this->getManager()->persist($userPostActivationProperties);
        $this->getManager()->flush();

        $this->assertNotNull($userPostActivationProperties->getId());
    }


    public function testPersistWithCoupon() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $user = $userService->create('user@example.com', 'password');
        $plan = $this->createAccountPlan();

        $userPostActivationProperties = new UserPostActivationProperties();
        $userPostActivationProperties->setUser($user);
        $userPostActivationProperties->setAccountPlan($plan);
        $userPostActivationProperties->setCoupon('FOO');

        $this->getManager()->persist($userPostActivationProperties);
        $this->getManager()->flush();

        $this->assertNotNull($userPostActivationProperties->getId());
    }

}
