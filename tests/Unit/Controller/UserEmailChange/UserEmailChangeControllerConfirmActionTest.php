<?php

namespace App\Tests\Unit\Controller\UserEmailChange;

use Mockery\Mock;
use App\Entity\User;
use App\Entity\UserEmailChangeRequest;
use App\Services\UserEmailChangeRequestService;
use App\Services\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Tests\Factory\MockFactory;

/**
 * @group Controller/UserEmailChangeController
 */
class UserEmailChangeControllerConfirmActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testConfirmActionUserHasNoRequest()
    {
        $user = new User();

        $userEmailChangeController = $this->createUserEmailChangeController([
            UserEmailChangeRequestService::class => MockFactory::createUserEmailChangeRequestService([
                'getForUser' => [
                    'with' => $user,
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(NotFoundHttpException::class);

        $userEmailChangeController->confirmAction(
            $user,
            $user->getEmail(),
            'token'
        );
    }

    public function testConfirmActionInvalidToken()
    {
        $user = new User();

        /* @var Mock|UserEmailChangeRequest $userEmailChangeRequest */
        $userEmailChangeRequest = \Mockery::mock(UserEmailChangeRequest::class);
        $userEmailChangeRequest
            ->shouldReceive('getToken')
            ->andReturn('foo');


        $userEmailChangeController = $this->createUserEmailChangeController([
            UserEmailChangeRequestService::class => MockFactory::createUserEmailChangeRequestService([
                'getForUser' => [
                    'with' => $user,
                    'return' => $userEmailChangeRequest,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userEmailChangeController->confirmAction(
            $user,
            $user->getEmail(),
            'token'
        );
    }

    public function testConfirmActionNewEmailTaken()
    {
        $user = new User();
        $token = 'token';
        $newEmail = 'foo@example.com';

        /* @var Mock|UserEmailChangeRequest $userEmailChangeRequest */
        $userEmailChangeRequest = \Mockery::mock(UserEmailChangeRequest::class);
        $userEmailChangeRequest
            ->shouldReceive('getToken')
            ->andReturn($token);

        $userEmailChangeRequest
            ->shouldReceive('getNewEmail')
            ->andReturn($newEmail);

        $userEmailChangeController = $this->createUserEmailChangeController([
            UserEmailChangeRequestService::class => MockFactory::createUserEmailChangeRequestService([
                'getForUser' => [
                    'with' => $user,
                    'return' => $userEmailChangeRequest,
                ],
                'removeForUser' => [
                    'with' => $user,
                ],
            ]),
            UserService::class => MockFactory::createUserService([
                'exists' => [
                    'with' => $newEmail,
                    'return' => true,
                ],
            ]),
        ]);

        $this->expectException(ConflictHttpException::class);

        $userEmailChangeController->confirmAction(
            $user,
            $user->getEmail(),
            $token
        );
    }
}
