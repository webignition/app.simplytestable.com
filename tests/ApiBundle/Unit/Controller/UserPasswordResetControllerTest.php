<?php

namespace Tests\ApiBundle\Unit\Controller;

use FOS\UserBundle\Util\UserManipulator;
use SimplyTestable\ApiBundle\Controller\UserPasswordResetController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/UserPasswordResetController
 */
class UserPasswordResetControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testResetPasswordActionInMaintenanceReadOnlyMode()
    {
        $userPasswordResetController = $this->createUserPasswordResetController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $userPasswordResetController->resetPasswordAction(new Request(), 'token');
    }

    public function testResetPasswordActionInvalidUser()
    {
        $userPasswordResetController = $this->createUserPasswordResetController([
            UserService::class => MockFactory::createUserService([
                'findUserByConfirmationToken' => [
                    'with' => 'token',
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $userPasswordResetController->resetPasswordAction(new Request(), 'token');
    }

    /**
     * @param array $services
     *
     * @return UserPasswordResetController
     */
    private function createUserPasswordResetController($services = [])
    {
        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }
        if (!isset($services[UserManipulator::class])) {
            $services[UserManipulator::class] = MockFactory::createUserManipulator();
        }

        $userPasswordResetController = new UserPasswordResetController(
            $services[ApplicationStateService::class],
            $services[UserService::class],
            $services[UserManipulator::class]
        );

        return $userPasswordResetController;
    }
}
