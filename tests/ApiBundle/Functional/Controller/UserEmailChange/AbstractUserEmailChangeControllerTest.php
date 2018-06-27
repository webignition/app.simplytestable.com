<?php

namespace Tests\ApiBundle\Functional\Controller\UserEmailChange;

use SimplyTestable\ApiBundle\Controller\UserEmailChangeController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Controller\AbstractControllerTest;

abstract class AbstractUserEmailChangeControllerTest extends AbstractControllerTest
{
    /**
     * @var UserEmailChangeController
     */
    protected $userEmailChangeController;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var User
     */
    protected $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userEmailChangeController = $this->container->get(UserEmailChangeController::class);

        $this->userFactory = new UserFactory($this->container);
        $this->user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'current-email@example.com',
        ]);

        $this->setUser($this->user);
    }

    /**
     * @param User $user
     * @param string $newEmail
     *
     * @return UserEmailChangeRequest
     */
    protected function createEmailChangeRequest(User $user, $newEmail)
    {
        $userEmailChangeRequestService = $this->container->get(UserEmailChangeRequestService::class);

        return $userEmailChangeRequestService->create($user, $newEmail);
    }
}
