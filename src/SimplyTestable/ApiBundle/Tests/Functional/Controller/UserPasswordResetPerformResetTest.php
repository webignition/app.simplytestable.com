<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserPasswordResetPerformResetTest extends BaseControllerJsonTestCase
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

    public function testPerformResetWithEncodedPassword()
    {
        $encodedNewPassword = rawurlencode('@password');

        $user = $this->userFactory->createAndActivateUser();

        $token = $this->getPasswordResetToken($user);

        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => $encodedNewPassword
        ));

        $response = $controller->resetPasswordAction($token);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPerformResetWithValidToken()
    {
        $user = $this->userFactory->createAndActivateUser();

        $token = $this->getPasswordResetToken($user);

        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => 'newpassword'
        ));

        $response = $controller->resetPasswordAction($token);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPerformResetWithInvalidToken()
    {
        $token = 'invalid token';

        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => 'newpassword'
        ));

        try {
            $response = $controller->resetPasswordAction($token);
            $this->fail('Attempt to reset password with invalid token did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testPerformResetWithInactiveUser()
    {
        $user = $this->userFactory->create();
        $token = $this->getPasswordResetToken($user);

        $controller = $this->getUserPasswordResetController('resetPasswordAction', array(
            'password' => 'newpassword'
        ));

        $response = $controller->resetPasswordAction($token);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
