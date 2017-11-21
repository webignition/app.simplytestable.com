<?php

namespace Tests\ApiBundle\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserPasswordResetController;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @group Controller/UserPasswordResetController
 */
class UserPasswordResetControllerTest extends AbstractBaseTestCase
{
    /**
     * @var UserPasswordResetController
     */
    private $userPasswordResetController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userPasswordResetController = $this->container->get(UserPasswordResetController::class);
    }

    public function testRequest()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_reset_password', [
            'token' => $user->getConfirmationToken(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'password' => 'new password',
            ],
            'user' => $user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testResetPasswordActionBadRequest()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $request = new Request();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('"password" missing');

        $this->userPasswordResetController->resetPasswordAction($request, $user->getConfirmationToken());
    }

    /**
     * @dataProvider resetPasswordActionDataProvider
     *
     * @param bool $activateUser
     * @param bool $expectedIsEnabledBefore
     * @param bool $expectedIsEnabledAfter
     */
    public function testResetPasswordAction($activateUser, $expectedIsEnabledBefore, $expectedIsEnabledAfter)
    {
        $userFactory = new UserFactory($this->container);

        if ($activateUser) {
            $user = $userFactory->createAndActivateUser();
        } else {
            $user = $userFactory->create();
        }

        $initialPassword = $user->getPassword();

        $this->assertEquals($expectedIsEnabledBefore, $user->isEnabled());

        $request = new Request([], [
            'password' => 'new password',
        ]);

        $response = $this->userPasswordResetController->resetPasswordAction($request, $user->getConfirmationToken());

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($expectedIsEnabledAfter, $user->isEnabled());
        $this->assertNotEquals($initialPassword, $user->getPassword());
    }

    /**
     * @return array
     */
    public function resetPasswordActionDataProvider()
    {
        return [
            'non-enabled user' => [
                'activateUser' => false,
                'expectedIsEnabledBefore' => false,
                'expectedIsEnabledAfter' => true,
            ],
            'enabled user' => [
                'activateUser' => true,
                'expectedIsEnabledBefore' => true,
                'expectedIsEnabledAfter' => true,
            ],
        ];
    }

}
