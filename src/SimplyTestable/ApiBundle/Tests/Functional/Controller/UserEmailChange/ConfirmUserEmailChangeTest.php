<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class ConfirmUserEmailChangeTest extends BaseControllerJsonTestCase {

    public function testForDifferentUser() {
        $email1 = 'user1@example.com';
        $password1 = 'password1';

        $user1 = $this->createAndActivateUser($email1, $password1);

        $email2 = 'user2@example.com';
        $password2 = 'password2';

        $user2 = $this->createAndActivateUser($email2, $password2);

        $this->getUserService()->setUser($user2);
        $this->getUserEmailChangeController('createAction')->createAction($user2->getEmail(), 'user1-new@example.com');

        $this->getUserService()->setUser($user1);

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction($user2->getEmail(), 'token');

            $this->fail('Attempt to confirm for different user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testWhereNoEmailChangeRequestExists() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndActivateUser($email, $password);
        $this->getUserService()->setUser($user);

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction($user->getEmail(), 'token');

            $this->fail('Attempt to confirm where no email change request exists did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }


    public function testWithInvalidToken() {
        $email = 'user1@example.com';
        $password = 'password1';

        $user = $this->createAndActivateUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), 'user1-new@example.com');

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction($user->getEmail(), 'token');

            $this->fail('Attempt to confirm with invalid token did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }


    public function testWhenNewEmailHasSinceBeenTakenByAnotherUser() {
        $email1 = 'user1@example.com';
        $password1 = 'password1';

        $user = $this->createAndActivateUser($email1, $password1);
        $this->getUserService()->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), 'user1-new@example.com');
        $emailChangeRequest = $this->getUserEmailChangeRequestService()->findByUser($user);

        $email2 = 'user1-new@example.com';
        $password2 = 'password2';

        $this->createAndActivateUser($email2, $password2);

        try {
            $this->getUserEmailChangeController('confirmAction')->confirmAction($user->getEmail(), $emailChangeRequest->getToken());

            $this->fail('Attempt to confirm when email already taken did not generate HTTP 409');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(409, $exception->getStatusCode());
            $this->assertInstanceOf('\SimplyTestable\ApiBundle\Entity\User', $this->getUserService()->findUserByEmail('user1-new@example.com'));
        }
    }


    public function testExpectedUsage() {
        $email = 'user1@example.com';
        $password = 'password1';
        $newEmail = 'user1-new@example.com';

        $user = $this->createAndActivateUser($email, $password);
        $this->getUserService()->setUser($user);

        $this->getUserEmailChangeController('createAction')->createAction($user->getEmail(), $newEmail);
        $emailChangeRequest = $this->getUserEmailChangeRequestService()->findByUser($user);

        $response = $this->getUserEmailChangeController('confirmAction')->confirmAction($user->getEmail(), $emailChangeRequest->getToken());
        $this->assertEquals(200, $response->getStatusCode());

        $this->getManager()->clear();

        $this->assertNull($this->getUserEmailChangeRequestService()->findByUser($user));
        $this->assertNull($this->getUserService()->findUserByEmail($email));
        $this->assertInstanceOf('\SimplyTestable\ApiBundle\Entity\User', $this->getUserService()->findUserByEmail($newEmail));
    }

}