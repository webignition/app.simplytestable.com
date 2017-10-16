<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserEmailChangeController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class GetUserEmailChangeTest extends BaseSimplyTestableTestCase
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

    public function testWithNonExistentUser()
    {
        $email = 'user1@example.com';

        try {
            $this->userEmailChangeController->getAction($email);
            $this->fail('Attempt to get for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWithValidUserThatHasNoEmailChangeRequest()
    {
        $email = 'user1@example.com';

        $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);

        try {
            $this->userEmailChangeController->getAction($email);
            $this->fail('Attempt to get where user does not have an email change request did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWithValidUserAndThatHasEmailChangeRequest()
    {
        $email = 'user1@example.com';
        $newEmail = 'user1-new@example.com';

        $user = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => $email,
        ]);
        $this->setUser($user);

        $this->userEmailChangeController->createAction($user->getEmail(), $newEmail);

        $response = $this->userEmailChangeController->getAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());

        $this->assertEquals($newEmail, $responseObject->new_email);
        $this->assertNotNull($responseObject->token);
    }
}
