<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserEmailChange;

use SimplyTestable\ApiBundle\Controller\UserEmailChangeController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

abstract class AbstractUserEmailChangeControllerTest extends BaseSimplyTestableTestCase
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

        $this->userEmailChangeController = new UserEmailChangeController();
        $this->userEmailChangeController->setContainer($this->container);

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
        $userEmailChangeRequestService = $this->container->get('simplytestable.services.useremailchangerequestservice');

        return $userEmailChangeRequestService->create($user, $newEmail);
    }
}
