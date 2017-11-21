<?php

namespace Tests\ApiBundle\Unit\Controller;

use FOS\UserBundle\Util\UserManipulator;
use SimplyTestable\ApiBundle\Controller\UserPasswordResetController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\MockFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
//
//
//    public function testResetPasswordActionBadRequest()
//    {
//        $userFactory = new UserFactory($this->container);
//        $user = $userFactory->create();
//
//        $request = new Request();
//
//        $this->expectException(BadRequestHttpException::class);
//        $this->expectExceptionMessage('"password" missing');
//
//        $this->userPasswordResetController->resetPasswordAction($request, $user->getConfirmationToken());
//    }
//
//    /**
//     * @dataProvider resetPasswordActionDataProvider
//     *
//     * @param bool $activateUser
//     * @param bool $expectedIsEnabledBefore
//     * @param bool $expectedIsEnabledAfter
//     */
//    public function testResetPasswordAction($activateUser, $expectedIsEnabledBefore, $expectedIsEnabledAfter)
//    {
//        $userFactory = new UserFactory($this->container);
//
//        if ($activateUser) {
//            $user = $userFactory->createAndActivateUser();
//        } else {
//            $user = $userFactory->create();
//        }
//
//        $initialPassword = $user->getPassword();
//
//        $this->assertEquals($expectedIsEnabledBefore, $user->isEnabled());
//
//        $request = new Request([], [
//            'password' => 'new password',
//        ]);
//
//        $response = $this->userPasswordResetController->resetPasswordAction($request, $user->getConfirmationToken());
//
//        $this->assertTrue($response->isSuccessful());
//        $this->assertEquals($expectedIsEnabledAfter, $user->isEnabled());
//        $this->assertNotEquals($initialPassword, $user->getPassword());
//    }
//
//    /**
//     * @return array
//     */
//    public function resetPasswordActionDataProvider()
//    {
//        return [
//            'non-enabled user' => [
//                'activateUser' => false,
//                'expectedIsEnabledBefore' => false,
//                'expectedIsEnabledAfter' => true,
//            ],
//            'enabled user' => [
//                'activateUser' => true,
//                'expectedIsEnabledBefore' => true,
//                'expectedIsEnabledAfter' => true,
//            ],
//        ];
//    }

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

        $jobConfigurationController = new UserPasswordResetController(
            $services[ApplicationStateService::class],
            $services[UserService::class],
            $services[UserManipulator::class]
        );

        return $jobConfigurationController;
    }
}
