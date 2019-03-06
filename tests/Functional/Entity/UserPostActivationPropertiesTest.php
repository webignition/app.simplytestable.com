<?php

namespace App\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Account\Plan\Plan;
use App\Entity\User;
use App\Tests\Services\PlanFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\UserPostActivationProperties;

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

        $userFactory = self::$container->get(UserFactory::class);
        $this->user = $userFactory->create();

        $planFactory = self::$container->get(PlanFactory::class);
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
