<?php

namespace Tests\AppBundle\Functional\Services;

use AppBundle\Entity\Account\Plan\Plan;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAccountPlan;
use AppBundle\Entity\UserEmailChangeRequest;
use AppBundle\Services\UserAccountPlanService;
use AppBundle\Services\UserEmailChangeRequestService;
use AppBundle\Services\UserService;
use Tests\AppBundle\Factory\StripeApiFixtureFactory;
use Tests\AppBundle\Factory\UserAccountPlanFactory;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

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

        $this->userEmailChangeRequestService = self::$container->get(UserEmailChangeRequestService::class);

        $userService = self::$container->get(UserService::class);
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
