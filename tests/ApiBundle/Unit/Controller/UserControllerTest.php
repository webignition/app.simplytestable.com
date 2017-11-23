<?php

namespace Tests\ApiBundle\Unit\Controller;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\ApiBundle\Factory\MockFactory;

/**
 * @group Controller/UserController
 */
class UserControllerTest extends \PHPUnit_Framework_TestCase
{
    const EMAIL = 'user@example.com';

    public function testExistsActionUserNotFound()
    {
        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'exists' => [
                    'with' => self::EMAIL,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $userController->existsAction(self::EMAIL);
    }

    public function testExistsActionSuccess()
    {
        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'exists' => [
                    'with' => self::EMAIL,
                    'return' => true,
                ],
            ]),
        ]);

        $response = $userController->existsAction(self::EMAIL);

        $this->assertTrue($response->isSuccessful());
    }

    public function testGetTokenActionUserNotFound()
    {
        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $userController->getTokenAction(self::EMAIL);
    }

    public function testGetTokenActionSuccess()
    {
        $user = new User();
        $confirmationToken = 'foo';

        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => $user,
                ],
                'getConfirmationToken' => [
                    'with' => $user,
                    'return' => $confirmationToken,
                ],
            ]),
        ]);

        $response = $userController->getTokenAction(self::EMAIL);

        $this->assertTrue($response->isSuccessful());

        $this->assertEquals(
            $confirmationToken,
            json_decode($response->getContent())
        );
    }

    public function testHasInvitesActionUserNotFound()
    {
        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $userController->hasInvitesAction(MockFactory::createTeamInviteService(), self::EMAIL);
    }

    public function testHasInvitesActionNoInvites()
    {
        $this->expectException(NotFoundHttpException::class);

        $user = new User();
        $confirmationToken = 'foo';

        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => $user,
                ],
                'getConfirmationToken' => [
                    'with' => $user,
                    'return' => $confirmationToken,
                ],
            ]),
        ]);

        $teamInviteService = MockFactory::createTeamInviteService([
            'hasAnyForUser' => [
                'with' => $user,
                'return' => false,
            ],
        ]);

        $userController->hasInvitesAction($teamInviteService, self::EMAIL);
    }

    public function testHasInvitesActionSuccess()
    {
        $user = new User();
        $confirmationToken = 'foo';

        $teamInviteService = MockFactory::createTeamInviteService([
            'hasAnyForUser' => [
                'with' => $user,
                'return' => true,
            ],
        ]);

        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => $user,
                ],
                'getConfirmationToken' => [
                    'with' => $user,
                    'return' => $confirmationToken,
                ],
            ]),
        ]);

        $response = $userController->hasInvitesAction($teamInviteService, self::EMAIL);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsEnabledActionUnknownUser()
    {
        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $userController->isEnabledAction(self::EMAIL);
    }

    public function testIsEnabledActionNotEnabledUser()
    {
        $user = new User();

        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => $user,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $userController->isEnabledAction(self::EMAIL);
    }

    public function testIsEnabledActionSuccess()
    {
        $user = new User();
        $user->setEnabled(true);

        $userController = $this->createUserController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => self::EMAIL,
                    'return' => $user,
                ],
            ]),
        ]);

        $response = $userController->isEnabledAction(self::EMAIL);

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @param array $services
     *
     * @return UserController
     */
    protected function createUserController($services)
    {
        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }

        return new UserController(
            $services[UserService::class]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
