<?php

namespace App\Tests\Functional\Controller\User;

use FOS\UserBundle\Util\UserManipulator;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Tests\Factory\MockFactory;
use App\Tests\Services\UserFactory;

/**
 * @group Controller/UserController
 */
class UserControllerResetPasswordActionTest extends AbstractUserControllerTest
{

    public function testResetPasswordActionPostRequest()
    {
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->createAndActivateUser();

        $router = self::$container->get('router');
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
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('"password" missing');

        $this->callResetPasswordAction(new Request(), $user);
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
        $userFactory = self::$container->get(UserFactory::class);

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

        $response = $this->callResetPasswordAction($request, $user);

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

    /**
     * @param Request $request
     * @param User $user
     *
     * @return Response
     */
    private function callResetPasswordAction(Request $request, User $user)
    {
        return $this->userController->resetPasswordAction(
            MockFactory::createApplicationStateService(),
            self::$container->get(UserManipulator::class),
            $request,
            $user->getConfirmationToken()
        );
    }
}
