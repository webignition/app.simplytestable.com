<?php

namespace SimplyTestable\ApiBundle\Tests\Entity;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;

class UserPostActivationPropertiesTest extends BaseSimplyTestableTestCase {

    public function testPersistWithoutCoupon() {
        $user = $this->getUserService()->create('user@example.com', 'password');
        $plan = $this->createAccountPlan();

        $userPostActivationProperties = new UserPostActivationProperties();
        $userPostActivationProperties->setUser($user);
        $userPostActivationProperties->setAccountPlan($plan);

        $this->getEntityManager()->persist($userPostActivationProperties);
        $this->getEntityManager()->flush();

        $this->assertNotNull($userPostActivationProperties->getId());
    }


    public function testPersistWithCoupon() {
        $user = $this->getUserService()->create('user@example.com', 'password');
        $plan = $this->createAccountPlan();

        $userPostActivationProperties = new UserPostActivationProperties();
        $userPostActivationProperties->setUser($user);
        $userPostActivationProperties->setAccountPlan($plan);
        $userPostActivationProperties->setCoupon('FOO');

        $this->getEntityManager()->persist($userPostActivationProperties);
        $this->getEntityManager()->flush();

        $this->assertNotNull($userPostActivationProperties->getId());
    }

}
