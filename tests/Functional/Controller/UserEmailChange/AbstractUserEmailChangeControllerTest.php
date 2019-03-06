<?php

namespace App\Tests\Functional\Controller\UserEmailChange;

use App\Controller\UserEmailChangeController;
use App\Entity\User;
use App\Entity\UserEmailChangeRequest;
use App\Services\UserEmailChangeRequestService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\Controller\AbstractControllerTest;

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

        $this->userEmailChangeController = self::$container->get(UserEmailChangeController::class);

        $this->userFactory = self::$container->get(UserFactory::class);
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
        $userEmailChangeRequestService = self::$container->get(UserEmailChangeRequestService::class);

        return $userEmailChangeRequestService->create($user, $newEmail);
    }
}
