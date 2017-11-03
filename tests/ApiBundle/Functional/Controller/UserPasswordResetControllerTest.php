<?php

namespace Tests\ApiBundle\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserPasswordResetController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

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

        $this->userPasswordResetController = new UserPasswordResetController();
        $this->userPasswordResetController->setContainer($this->container);
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

    public function testResetPasswordActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->userPasswordResetController->resetPasswordAction(new Request(), 'foo');
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    public function testResetPasswordActionInvalidUser()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->userPasswordResetController->resetPasswordAction(new Request(), 'invalid token');
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
