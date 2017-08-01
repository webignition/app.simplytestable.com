<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class GetTokenTest extends BaseControllerJsonTestCase
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

    public function testGetTokenWithNotEnabledUser() {
        $user = $this->userFactory->create();

        $controller = $this->getUserController('getTokenAction');
        $response = $controller->getTokenAction($user->getEmail());

        $token = $this->getUserService()->getConfirmationToken($user);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));
    }

    public function testGetTokenWithNonExistentUser() {
        $email = 'user1@example.com';

        try {
            $controller = $this->getUserController('getTokenAction');
            $controller->getTokenAction($email);
            $this->fail('Attempt to get token for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }


    public function testGetTokenWithEnabledUser() {
        $user = $this->userFactory->createAndActivateUser();

        $controller = $this->getUserController('getTokenAction');
        $response = $controller->getTokenAction($user->getEmail());

        $token = $this->getUserService()->getConfirmationToken($user);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));
    }
}


