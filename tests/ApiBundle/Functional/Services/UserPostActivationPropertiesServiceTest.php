<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\UserAccountPlanFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

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

        $this->userPostActivationPropertiesService = $this->container->get(
            'simplytestable.services.job.userpostactivationpropertiesservice'
        );

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->user = $userService->getPublicUser();

        $this->accountPlanService = $this->container->get('simplytestable.services.accountplan');
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
