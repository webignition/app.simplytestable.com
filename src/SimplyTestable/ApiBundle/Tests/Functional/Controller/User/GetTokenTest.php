<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class GetTokenTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserController
     */
    private $userController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userController = new UserController();
        $this->userController->setContainer($this->container);
    }

    public function testGetTokenWithNotEnabledUser() {
        $userService = $this->container->get('simplytestable.services.userservice');

        $user = $this->userFactory->create();

        $response = $this->userController->getTokenAction($user->getEmail());

        $token = $userService->getConfirmationToken($user);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));
    }

    public function testGetTokenWithNonExistentUser() {
        $email = 'user1@example.com';

        try {
            $this->userController->getTokenAction($email);
            $this->fail('Attempt to get token for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }


    public function testGetTokenWithEnabledUser() {
        $userService = $this->container->get('simplytestable.services.userservice');
        $user = $this->userFactory->createAndActivateUser();

        $response = $this->userController->getTokenAction($user->getEmail());

        $token = $userService->getConfirmationToken($user);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));
    }
}


