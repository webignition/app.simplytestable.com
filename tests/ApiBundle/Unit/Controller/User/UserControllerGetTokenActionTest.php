<?php

namespace Tests\ApiBundle\Unit\Controller\User;

use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\ApiBundle\Factory\MockFactory;

/**
 * @group Controller/UserController
 */
class UserControllerGetTokenActionTest extends AbstractUserControllerTest
{
    const EMAIL = 'user@example.com';

    public function testGetTokenActionUserNotFound()
    {
        $userService = MockFactory::createUserService([
            'findUserByEmail' => [
                'with' => self::EMAIL,
                'return' => null,
            ],
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->userController->getTokenAction(
            $userService,
            self::EMAIL
        );
    }

    public function testGetTokenActionSuccess()
    {
        $user = new User();
        $confirmationToken = 'foo';

        $userService = MockFactory::createUserService([
            'findUserByEmail' => [
                'with' => self::EMAIL,
                'return' => $user,
            ],
            'getConfirmationToken' => [
                'with' => $user,
                'return' => $confirmationToken,
            ],
        ]);

        $response = $this->userController->getTokenAction(
            $userService,
            self::EMAIL
        );

        $this->assertTrue($response->isSuccessful());

        $this->assertEquals(
            $confirmationToken,
            json_decode($response->getContent())
        );
    }
}
