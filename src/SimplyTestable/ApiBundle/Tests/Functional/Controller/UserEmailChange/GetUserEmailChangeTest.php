<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class GetUserEmailChangeTest extends BaseControllerJsonTestCase
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

    public function testWithNonExistentUser()
    {
        $email = 'user1@example.com';
        $controller = $this->getUserEmailChangeController('getAction');

        try {
            $controller->getAction($email);
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

        $controller = $this->getUserEmailChangeController('getAction');

        try {
            $controller->getAction($email);
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
        $this->getUserService()->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), $newEmail);

        $response = $this->getUserEmailChangeController('getAction')->getAction($user->getEmail());

        $this->assertEquals(200, $response->getStatusCode());

        $responseObject = json_decode($response->getContent());

        $this->assertEquals($newEmail, $responseObject->new_email);
        $this->assertNotNull($responseObject->token);
    }
}
