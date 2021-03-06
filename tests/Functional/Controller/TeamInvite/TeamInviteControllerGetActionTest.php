<?php

namespace App\Tests\Functional\Controller\TeamInvite;

use App\Entity\Team\Invite;
use App\Entity\User;
use App\Services\Team\InviteService;
use App\Tests\Services\UserAccountPlanFactory;
use App\Tests\Services\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/TeamInviteController
 */
class TeamInviteControllerGetActionTest extends AbstractTeamInviteControllerTest
{
    public function testGetActionGetRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('teaminvite_get', [
            'invitee_email' => 'new-user@example.com',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->users['leader'],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider getActionClientFailureDataProvider
     *
     * @param string $inviterName
     * @param string $inviteeEmail
     * @param array $expectedResponseError
     */
    public function testGetActionClientFailure($inviterName, $inviteeEmail, $expectedResponseError)
    {
        $userAccountPlanFactory = self::$container->get(UserAccountPlanFactory::class);
        $userAccountPlanFactory->create($this->users['private'], 'agency');

        $inviter = $this->users[$inviterName];

        $response = $this->teamInviteController->getAction($inviter, new Request(), $inviteeEmail);

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            $expectedResponseError,
            [
                'code' => $response->headers->get('X-Teaminviteget-Error-Code'),
                'message' => $response->headers->get('X-Teaminviteget-Error-Message'),
            ]
        );
    }

    /**
     * @return array
     */
    public function getActionClientFailureDataProvider()
    {
        return [
            'inviter is a team leader' => [
                'inviterName' => 'member1',
                'inviteeEmail' => 'foo@example.com',
                'expectedResponseError' => [
                    'code' => 1,
                    'message' => 'Inviter is not a team leader',
                ],
            ],
            'public user cannot be invited' => [
                'inviterName' => 'leader',
                'inviteeEmail' => 'public@simplytestable.com',
                'expectedResponseError' => [
                    'code' => 10,
                    'message' => 'Special users cannot be invited',
                ],
            ],
            'admin user cannot be invited' => [
                'inviterName' => 'leader',
                'inviteeEmail' => 'admin@simplytestable.com',
                'expectedResponseError' => [
                    'code' => 10,
                    'message' => 'Special users cannot be invited',
                ],
            ],
            'invitee has premium plan' => [
                'inviterName' => 'leader',
                'inviteeEmail' => 'private@example.com',
                'expectedResponseError' => [
                    'code' => 11,
                    'message' => 'Invitee has a premium plan',
                ],
            ],
            'invitee is a team leader' => [
                'inviterName' => 'leader',
                'inviteeEmail' => 'leader@example.com',
                'expectedResponseError' => [
                    'code' => 2,
                    'message' => 'Invitee is a team leader',
                ],
            ],
            'invitee is on a team' => [
                'inviterName' => 'leader',
                'inviteeEmail' => 'member1@example.com',
                'expectedResponseError' => [
                    'code' => 3,
                    'message' => 'Invitee is on a team',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getActionSuccessDataProvider
     *
     * @param string $inviteeEmail
     * @param bool $expectedUserAlreadyExists
     * @param bool $expectedInviteAlreadyExists
     */
    public function testGetActionSuccess(
        $inviteeEmail,
        $expectedUserAlreadyExists,
        $expectedInviteAlreadyExists
    ) {
        $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'new-user-no-invite@example.com',
        ]);

        $newUserHasInvite = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'new-user-has-invite@example.com',
        ]);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $teamInviteService = self::$container->get(InviteService::class);
        $userRepository = $entityManager->getRepository(User::class);
        $inviteRepository = $entityManager->getRepository(Invite::class);

        $teamInviteService->get($this->users['leader'], $newUserHasInvite);

        $invitee = $userRepository->findOneBy([
            'email' => $inviteeEmail,
        ]);

        $this->assertEquals($expectedUserAlreadyExists, !is_null($invitee));

        $invite = $inviteRepository->findOneBy([
            'user' => $invitee,
        ]);

        $this->assertEquals($expectedInviteAlreadyExists, !is_null($invite));

        $inviter = $this->users['leader'];

        $response = $this->teamInviteController->getAction($inviter, new Request(), $inviteeEmail);

        $this->assertTrue($response->isSuccessful());

        $invitee = $userRepository->findOneBy([
            'email' => $inviteeEmail,
        ]);

        $this->assertNotNull($invitee);

        /* @var Invite $invite */
        $invite = $inviteRepository->findOneBy([
            'user' => $invitee,
        ]);

        $this->assertNotNull($invite);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals($invite->getTeam()->getName(), $responseData['team']);
        $this->assertEquals($invitee->getEmail(), $responseData['user']);
        $this->assertEquals($invite->getToken(), $responseData['token']);
    }

    /**
     * @return array
     */
    public function getActionSuccessDataProvider()
    {
        return [
            'invitee does not already exist, invite does not already exist' => [
                'inviteeEmail' => 'foo@example.com',
                'expectedUserAlreadyExists' => false,
                'expectedInviteAlreadyExists' => false,
            ],
            'invitee already exists, invite does not already exist' => [
                'inviteeEmail' => 'new-user-no-invite@example.com',
                'expectedUserAlreadyExists' => true,
                'expectedInviteAlreadyExists' => false,
            ],
            'invitee already exists, invite already exists' => [
                'inviteeEmail' => 'new-user-has-invite@example.com',
                'expectedUserAlreadyExists' => true,
                'expectedInviteAlreadyExists' => true,
            ],
        ];
    }
}
