<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetTokenTest extends BaseControllerJsonTestCase {

    public function testGetTokenWithNotEnabledUser() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndFindUser($email, $password);

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
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndActivateUser($email, $password);

        $controller = $this->getUserController('getTokenAction');
        $response = $controller->getTokenAction($user->getEmail());

        $token = $this->getUserService()->getConfirmationToken($user);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($token, json_decode($response->getContent()));
    }
}

