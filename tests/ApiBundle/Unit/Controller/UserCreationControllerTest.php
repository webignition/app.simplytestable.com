<?php

namespace Tests\ApiBundle\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\UserManipulator;
use Mockery\Mock;
use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/UserCreationController
 */
class UserCreationControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $userCreationController = $this->createUserCreationController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $userCreationController->activateAction('token');
    }

    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $userCreationController = $this->createUserCreationController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $userCreationController->createAction(new Request());
    }

    /**
     * @dataProvider createActionBadRequestDataProvider
     *
     * @param string $email
     * @param string $password
     * @param string $expectedExceptionMessage
     */
    public function testCreateActionBadRequest($email, $password, $expectedExceptionMessage)
    {
        $request = new Request([], [
            'email' => $email,
            'password' => $password,
        ]);

        $userCreationController = $this->createUserCreationController();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $userCreationController->createAction($request);
    }

    /**
     * @return array
     */
    public function createActionBadRequestDataProvider()
    {
        return [
            'no email no password' => [
                'email' => null,
                'password' => null,
                'expectedExceptionMessage' => '"email" missing',
            ],
            'has email no password' => [
                'email' => 'user@example.com',
                'password' => null,
                'expectedExceptionMessage' => '"password" missing',
            ],
            'no email has password' => [
                'email' => null,
                'password' => 'password',
                'expectedExceptionMessage' => '"email" missing',
            ],
        ];
    }

    /**
     * @dataProvider activateActionEmptyTokenDataProvider
     *
     * @param string $token
     */
    public function testActivateActionEmptyToken($token)
    {
        $userCreationController = $this->createUserCreationController();

        $this->expectException(BadRequestHttpException::class);

        $userCreationController->activateAction($token);
    }

    /**
     * @return array
     */
    public function activateActionEmptyTokenDataProvider()
    {
        return [
            'null' => [
                'token' => null,
            ],
            'empty string' => [
                'token' => '',
            ],
            'whitespace string' => [
                'token' => ' ',
            ],
        ];
    }

    /**
     * @param array $services
     *
     * @return UserCreationController
     */
    private function createUserCreationController($services = [])
    {
        if (!isset($services['router'])) {
            /* @var RouterInterface|Mock $router */
            $router = \Mockery::mock(RouterInterface::class);

            $services['router'] = $router;
        }

        if (!isset($services[ApplicationStateService::class])) {
            $services[ApplicationStateService::class] = MockFactory::createApplicationStateService();
        }

        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }

        if (!isset($services[UserAccountPlanService::class])) {
            $services[UserAccountPlanService::class] = MockFactory::createUserAccountPlanService();
        }

        if (!isset($services[UserPostActivationPropertiesService::class])) {
            $services[UserPostActivationPropertiesService::class] =
                MockFactory::createUserPostActivationPropertiesService();
        }

        if (!isset($services[AccountPlanService::class])) {
            $services[AccountPlanService::class] = MockFactory::createAccountPlanService();
        }

        if (!isset($services[EntityManagerInterface::class])) {
            $services[EntityManagerInterface::class] = MockFactory::createEntityManager();
        }

        if (!isset($services[UserManipulator::class])) {
            $services[UserManipulator::class] = MockFactory::createUserManipulator();
        }

        $userCreationController = new UserCreationController(
            $services['router'],
            $services[ApplicationStateService::class],
            $services[UserService::class],
            $services[UserAccountPlanService::class],
            $services[UserPostActivationPropertiesService::class],
            $services[AccountPlanService::class],
            $services[EntityManagerInterface::class],
            $services[UserManipulator::class]
        );

        return $userCreationController;
    }
}
