<?php

namespace Tests\ApiBundle\Unit\Controller\User;

use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Controller/UserController
 */
class UserControllerHasInvitesActionTest extends AbstractUserControllerTest
{
    const EMAIL = 'user@example.com';

    public function testHasInvitesActionUserNotFound()
    {
        $userService = MockFactory::createUserService([
            'findUserByEmail' => [
                'with' => self::EMAIL,
                'return' => null,
            ],
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->userController->hasInvitesAction(
            $userService,
            MockFactory::createTeamInviteService(),
            self::EMAIL
        );
    }

    public function testHasInvitesActionNoInvites()
    {
        $this->expectException(NotFoundHttpException::class);

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

        $teamInviteService = MockFactory::createTeamInviteService([
            'hasAnyForUser' => [
                'with' => $user,
                'return' => false,
            ],
        ]);

        $this->userController->hasInvitesAction(
            $userService,
            $teamInviteService,
            self::EMAIL
        );
    }

    public function testHasInvitesActionSuccess()
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

        $teamInviteService = MockFactory::createTeamInviteService([
            'hasAnyForUser' => [
                'with' => $user,
                'return' => true,
            ],
        ]);

        $this->userController->hasInvitesAction(
            $userService,
            $teamInviteService,
            self::EMAIL
        );

        $response = $this->userController->hasInvitesAction(
            $userService,
            $teamInviteService,
            self::EMAIL
        );

        $this->assertTrue($response->isSuccessful());
    }
}
