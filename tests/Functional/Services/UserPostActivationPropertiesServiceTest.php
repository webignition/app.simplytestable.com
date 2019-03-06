<?php

namespace App\Tests\Functional\Services;

use App\Entity\User;
use App\Entity\UserPostActivationProperties;
use App\Services\AccountPlanService;
use App\Services\UserPostActivationPropertiesService;
use App\Services\UserService;
use App\Tests\Functional\AbstractBaseTestCase;

class UserPostActivationPropertiesServiceTest extends AbstractBaseTestCase
{
    /**
     * @var UserPostActivationPropertiesService
     */
    private $userPostActivationPropertiesService;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @var User
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userPostActivationPropertiesService = self::$container->get(UserPostActivationPropertiesService::class);

        $userService = self::$container->get(UserService::class);
        $this->user = $userService->getPublicUser();

        $this->accountPlanService = self::$container->get(AccountPlanService::class);
    }

    public function testCreate()
    {
        $personalAccountPlan = $this->accountPlanService->get('personal');
        $agencyAccountPlan = $this->accountPlanService->get('agency');

        $postActivationProperties = $this->userPostActivationPropertiesService->create(
            $this->user,
            $personalAccountPlan
        );

        $this->assertInstanceOf(UserPostActivationProperties::class, $postActivationProperties);
        $this->assertNotNull($postActivationProperties->getId());
        $this->assertEquals($this->user, $postActivationProperties->getUser());
        $this->assertEquals($personalAccountPlan, $postActivationProperties->getAccountPlan());
        $this->assertNull($postActivationProperties->getCoupon());

        $updatedPostActivationProperties = $this->userPostActivationPropertiesService->create(
            $this->user,
            $agencyAccountPlan,
            'TMS'
        );

        $this->assertEquals($postActivationProperties->getId(), $updatedPostActivationProperties->getId());
        $this->assertEquals($this->user, $updatedPostActivationProperties->getUser());
        $this->assertEquals($agencyAccountPlan, $updatedPostActivationProperties->getAccountPlan());
        $this->assertEquals('TMS', $updatedPostActivationProperties->getCoupon());
    }
}
