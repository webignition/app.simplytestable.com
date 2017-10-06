<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserPasswordResetController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserPasswordResetControllerTest extends BaseSimplyTestableTestCase
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
        $user = $userFactory->create();

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
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $response = $this->userPasswordResetController->resetPasswordAction(new Request(), 'foo');
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY);

        $response = $this->userPasswordResetController->resetPasswordAction(new Request(), 'foo');
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testResetPasswordActionInvalidUser()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->userPasswordResetController->resetPasswordAction(new Request(), 'invalid token');
    }


    public function testResetPasswordActionBadRequest()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $request = new Request();

        $this->setExpectedException(
            BadRequestHttpException::class,
            '"password" missing'
        );

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
