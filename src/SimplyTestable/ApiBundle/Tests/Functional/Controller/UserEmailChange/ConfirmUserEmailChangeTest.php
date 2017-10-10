<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ConfirmUserEmailChangeTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testForDifferentUser()
    {
        $email1 = 'user1@example.com';
        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email1,
        ]);

        $email2 = 'user2@example.com';
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email2,
        ]);

        $this->setUser($user2);
        $this->getUserEmailChangeController('createAction')->createAction($user2->getEmail(), 'user1-new@example.com');

        $this->setUser($user1);

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction($user2->getEmail(), 'token');

            $this->fail('Attempt to confirm for different user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWhereNoEmailChangeRequestExists()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction($user->getEmail(), 'token');

            $this->fail('Attempt to confirm where no email change request exists did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWithInvalidToken()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), 'user1-new@example.com');

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction($user->getEmail(), 'token');

            $this->fail('Attempt to confirm with invalid token did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }

    public function testWhenNewEmailHasSinceBeenTakenByAnotherUser()
    {
        $email1 = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email1,
        ]);
        $this->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), 'user1-new@example.com');
        $emailChangeRequest = $this->getUserEmailChangeRequestService()->findByUser($user);

        $email2 = 'user1-new@example.com';
        $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email2,
        ]);

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction(
                $user->getEmail(),
                $emailChangeRequest->getToken()
            );

            $this->fail('Attempt to confirm when email already taken did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $userService = $this->container->get('simplytestable.services.userservice');

            $this->assertEquals(409, $exception->getStatusCode());
            $this->assertInstanceOf(User::class, $userService->findUserByEmail('user1-new@example.com'));
        }
    }

    public function testExpectedUsage()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $email = 'user1@example.com';
        $newEmail = 'user1-new@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), $newEmail);
        $emailChangeRequest = $this->getUserEmailChangeRequestService()->findByUser($user);

        $response = $this->getUserEmailChangeController('confirmAction')->confirmAction(
            $user->getEmail(),
            $emailChangeRequest->getToken()
        );
        $this->assertEquals(200, $response->getStatusCode());

        $this->getManager()->clear();

        $this->assertNull($this->getUserEmailChangeRequestService()->findByUser($user));
        $this->assertNull($userService->findUserByEmail($email));
        $this->assertInstanceOf(User::class, $userService->findUserByEmail($newEmail));
    }
}
