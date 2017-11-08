<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\UserAccountPlanFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

class UserEmailChangeRequestServiceTest extends AbstractBaseTestCase
{
    /**
     * @var UserEmailChangeRequestService
     */
    private $userEmailChangeRequestService;

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

        $this->userEmailChangeRequestService = $this->container->get(
            'simplytestable.services.useremailchangerequestservice'
        );

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->user = $userService->getPublicUser();
    }

    public function testGetForUser()
    {
        $emailChangeRequest = $this->userEmailChangeRequestService->getForUser($this->user);
        $this->assertNull($emailChangeRequest);

        $this->userEmailChangeRequestService->create($this->user, 'foo@example.com');

        $emailChangeRequest = $this->userEmailChangeRequestService->getForUser($this->user);
        $this->assertInstanceOf(UserEmailChangeRequest::class, $emailChangeRequest);
    }

    public function testRemoveForUser()
    {
        $this->userEmailChangeRequestService->removeForUser($this->user);

        $emailChangeRequest = $this->userEmailChangeRequestService->create($this->user, 'foo@example.com');

        $this->assertNotNull($emailChangeRequest->getId());

        $this->userEmailChangeRequestService->removeForUser($this->user);

        $this->assertNull($emailChangeRequest->getId());
    }

    public function testCreate()
    {
        $newEmail = 'foo@example.com';

        $emailChangeRequest = $this->userEmailChangeRequestService->create($this->user, $newEmail);

        $this->assertInstanceOf(UserEmailChangeRequest::class, $emailChangeRequest);
        $this->assertEquals($newEmail, $emailChangeRequest->getNewEmail());
        $this->assertNotNull($emailChangeRequest->getToken());
        $this->assertEquals($this->user, $emailChangeRequest->getUser());

        $this->assertEquals(
            $emailChangeRequest,
            $this->userEmailChangeRequestService->create($this->user, $newEmail)
        );
    }
}
