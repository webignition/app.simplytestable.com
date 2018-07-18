<?php

namespace Tests\AppBundle\Functional\Controller\UserEmailChange;

use AppBundle\Controller\UserEmailChangeController;
use AppBundle\Entity\User;
use AppBundle\Entity\UserEmailChangeRequest;
use AppBundle\Services\UserEmailChangeRequestService;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

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

        $this->userFactory = new UserFactory(self::$container);
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
