<?php

namespace Tests\ApiBundle\Unit\Controller\User;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\ApiBundle\Factory\MockFactory;

/**
 * @group Controller/UserController
 */
class UserControllerExistsActionTest extends AbstractUserControllerTest
{
    const EMAIL = 'user@example.com';

    public function testExistsActionUserNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $userService = MockFactory::createUserService([
            'exists' => [
                'with' => self::EMAIL,
                'return' => null,
            ],
        ]);

        $this->userController->existsAction(
            $userService,
            self::EMAIL
        );
    }

    public function testExistsActionSuccess()
    {
        $userService = MockFactory::createUserService([
            'exists' => [
                'with' => self::EMAIL,
                'return' => true,
            ],
        ]);

        $response = $this->userController->existsAction(
            $userService,
            self::EMAIL
        );

        $this->assertTrue($response->isSuccessful());
    }
}
