<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CreateUserEmailChangeTest extends BaseControllerJsonTestCase
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

    public function testWithNotEnabledUser()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->create($email);
        $this->getUserService()->setUser($user);

        $controller = $this->getUserEmailChangeController('createAction');

        try {
            $controller->createAction($user->getEmail(), 'new_email');
            $this->fail('Attempt to create for not-enabled user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWithNonExistentUser()
    {
        $email = 'user1@example.com';
        $controller = $this->getUserEmailChangeController('createAction');

        try {
            $controller->createAction($email, 'new_email');
            $this->fail('Attempt to create for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWithInvalidNewEmail()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser($email);
        $this->getUserService()->setUser($user);
        $controller = $this->getUserEmailChangeController('createAction');

        try {
            $controller->createAction($user->getEmail(), 'new_email');
            $this->fail('Attempt to create with invalid new email did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }

    public function testWhereNewEmailIsExistingUser()
    {
        $email1 = 'user1@example.com';
        $user1 = $this->userFactory->createAndActivateUser($email1);

        $email2 = 'user2@example.com';
        $user2 = $this->userFactory->createAndActivateUser($email2);

        $this->getUserService()->setUser($user1);

        $controller = $this->getUserEmailChangeController('createAction');

        try {
            $controller->createAction($user1->getEmail(), $user2->getEmail());
            $this->fail('Attempt to create with email of existing user did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());
        }
    }

    public function testWhereNewEmailIsExistingEmailChangeRequestForDifferentUser()
    {
        $email1 = 'user1@example.com';

        $user1 = $this->userFactory->createAndActivateUser($email1);
        $this->getUserService()->setUser($user1);

        $controller = $this->getUserEmailChangeController('createAction');
        $controller->createAction($user1->getEmail(), 'user1-new@example.com');

        $email2 = 'user2@example.com';
        $user2 = $this->userFactory->createAndActivateUser($email2);

        $this->getUserService()->setUser($user2);

        try {
            $controller->createAction($user2->getEmail(), 'user1-new@example.com');

            $this->fail('Attempt to create with email of existing change request did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());
        }
    }

    public function testWhereUserAlreadyHasEmailChangeRequestForSameChange()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser($email);
        $this->getUserService()->setUser($user);

        $controller = $this->getUserEmailChangeController('createAction');

        $this->assertEquals(
            200,
            $controller->createAction($user->getEmail(), 'user1-new@example.com')->getStatusCode()
        );
        $this->assertEquals(
            200,
            $controller->createAction($user->getEmail(), 'user1-new@example.com')->getStatusCode()
        );
    }

    public function testWhereUserAlreadyHasEmailChangeRequestForDifferentChange()
    {
        try {
            $email = 'user1@example.com';

            $user = $this->userFactory->createAndActivateUser($email);
            $this->getUserService()->setUser($user);

            $controller = $this->getUserEmailChangeController('createAction');
            $controller->createAction($user->getEmail(), 'user1-new1@example.com');
            $controller->createAction($user->getEmail(), 'user1-new2@example.com');

            $this->fail('Attempt to create with email of existing change request did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());
        }
    }

    public function testForDifferentUser()
    {
        $email1 = 'user1@example.com';
        $user1 = $this->userFactory->createAndActivateUser($email1);

        $email2 = 'user2@example.com';
        $user2 = $this->userFactory->createAndActivateUser($email2);

        $this->getUserService()->setUser($user1);

        try {
            $controller = $this->getUserEmailChangeController('createAction');
            $controller->createAction($user2->getEmail(), 'user1-new@example.com');

            $this->fail('Attempt to create for different user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testForCorrectUser()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser($email);
        $this->getUserService()->setUser($user);

        $controller = $this->getUserEmailChangeController('createAction');
        $response = $controller->createAction($user->getEmail(), 'user1-new@example.com');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
