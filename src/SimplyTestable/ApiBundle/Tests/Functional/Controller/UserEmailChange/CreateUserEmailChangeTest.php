<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserEmailChangeController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CreateUserEmailChangeTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserEmailChangeController
     */
    private $userEmailChangeController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userEmailChangeController = new UserEmailChangeController();
        $this->userEmailChangeController->setContainer($this->container);
    }

    public function testWithNotEnabledUser()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        try {
            $this->userEmailChangeController->createAction($user->getEmail(), 'new_email');
            $this->fail('Attempt to create for not-enabled user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWithNonExistentUser()
    {
        $email = 'user1@example.com';

        try {
            $this->userEmailChangeController->createAction($email, 'new_email');
            $this->fail('Attempt to create for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWithInvalidNewEmail()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        try {
            $this->userEmailChangeController->createAction($user->getEmail(), 'new_email');
            $this->fail('Attempt to create with invalid new email did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }

    public function testWhereNewEmailIsExistingUser()
    {
        $email1 = 'user1@example.com';
        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email1,
        ]);

        $email2 = 'user2@example.com';
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email2,
        ]);

        $this->setUser($user1);

        try {
            $this->userEmailChangeController->createAction($user1->getEmail(), $user2->getEmail());
            $this->fail('Attempt to create with email of existing user did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());
        }
    }

    public function testWhereNewEmailIsExistingEmailChangeRequestForDifferentUser()
    {
        $email1 = 'user1@example.com';

        $user1 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email1,
        ]);
        $this->setUser($user1);

        $this->userEmailChangeController->createAction($user1->getEmail(), 'user1-new@example.com');

        $email2 = 'user2@example.com';
        $user2 = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email2,
        ]);

        $this->setUser($user2);

        try {
            $this->userEmailChangeController->createAction($user2->getEmail(), 'user1-new@example.com');

            $this->fail('Attempt to create with email of existing change request did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());
        }
    }

    public function testWhereUserAlreadyHasEmailChangeRequestForSameChange()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $this->assertEquals(
            200,
            $this->userEmailChangeController->createAction($user->getEmail(), 'user1-new@example.com')->getStatusCode()
        );
        $this->assertEquals(
            200,
            $this->userEmailChangeController->createAction($user->getEmail(), 'user1-new@example.com')->getStatusCode()
        );
    }

    public function testWhereUserAlreadyHasEmailChangeRequestForDifferentChange()
    {
        try {
            $email = 'user1@example.com';

            $user = $this->userFactory->createAndActivateUser([
                UserFactory::KEY_EMAIL => $email,
            ]);
            $this->setUser($user);

            $this->userEmailChangeController->createAction($user->getEmail(), 'user1-new1@example.com');
            $this->userEmailChangeController->createAction($user->getEmail(), 'user1-new2@example.com');

            $this->fail('Attempt to create with email of existing change request did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());
        }
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

        $this->setUser($user1);

        try {
            $this->userEmailChangeController->createAction($user2->getEmail(), 'user1-new@example.com');

            $this->fail('Attempt to create for different user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testForCorrectUser()
    {
        $email = 'user1@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $response = $this->userEmailChangeController->createAction($user->getEmail(), 'user1-new@example.com');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
