<?php

namespace Tests\ApiBundle\Unit\Controller\User;

use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/UserController
 */
class UserControllerIsEnabledActionTest extends AbstractUserControllerTest
{
    const EMAIL = 'user@example.com';

    public function testIsEnabledActionUnknownUser()
    {
        $userService = MockFactory::createUserService([
            'findUserByEmail' => [
                'with' => self::EMAIL,
                'return' => null,
            ],
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->userController->isEnabledAction(
            $userService,
            self::EMAIL
        );
    }

    public function testIsEnabledActionNotEnabledUser()
    {
        $user = MockFactory::createUser([
            'isEnabled' => [
                'return' => false,
            ],
        ]);

        $userService = MockFactory::createUserService([
            'findUserByEmail' => [
                'with' => self::EMAIL,
                'return' => $user,
            ],
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->userController->isEnabledAction(
            $userService,
            self::EMAIL
        );
    }

    public function testIsEnabledActionSuccess()
    {
        $user = MockFactory::createUser([
            'isEnabled' => [
                'return' => true,
            ],
        ]);

        $userService = MockFactory::createUserService([
            'findUserByEmail' => [
                'with' => self::EMAIL,
                'return' => $user,
            ],
        ]);

        $response = $this->userController->isEnabledAction(
            $userService,
            self::EMAIL
        );

        $this->assertTrue($response->isSuccessful());
    }
}
