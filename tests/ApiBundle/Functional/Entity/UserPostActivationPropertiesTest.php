<?php

namespace Tests\ApiBundle\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\PlanFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
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

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory(self::$container);
        $this->user = $userFactory->create();

        $planFactory = new PlanFactory(self::$container);
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
