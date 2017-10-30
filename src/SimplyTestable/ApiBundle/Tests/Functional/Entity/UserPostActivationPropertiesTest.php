<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\PlanFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;

class UserPostActivationPropertiesTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Plan
     */
    private $plan;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->create();

        $planFactory = new PlanFactory($this->container);
        $this->plan = $planFactory->create();
    }

    public function testPersistWithoutCoupon()
    {
        $userPostActivationProperties = new UserPostActivationProperties();
        $userPostActivationProperties->setUser($this->user);
        $userPostActivationProperties->setAccountPlan($this->plan);

        $this->entityManager->persist($userPostActivationProperties);
        $this->entityManager->flush();

        $this->assertNotNull($userPostActivationProperties->getId());
    }

    public function testPersistWithCoupon()
    {
        $userPostActivationProperties = new UserPostActivationProperties();
        $userPostActivationProperties->setUser($this->user);
        $userPostActivationProperties->setAccountPlan($this->plan);
        $userPostActivationProperties->setCoupon('FOO');

        $this->entityManager->persist($userPostActivationProperties);
        $this->entityManager->flush();

        $this->assertNotNull($userPostActivationProperties->getId());
    }
}
