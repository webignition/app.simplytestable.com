<?php

namespace Tests\AppBundle\Functional\Services;

use AppBundle\Entity\Account\Plan\Plan;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAccountPlan;
use AppBundle\Entity\UserEmailChangeRequest;
use AppBundle\Entity\UserPostActivationProperties;
use AppBundle\Services\AccountPlanService;
use AppBundle\Services\UserAccountPlanService;
use AppBundle\Services\UserEmailChangeRequestService;
use AppBundle\Services\UserPostActivationPropertiesService;
use AppBundle\Services\UserService;
use Tests\AppBundle\Factory\StripeApiFixtureFactory;
use Tests\AppBundle\Factory\UserAccountPlanFactory;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

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
