<?php

namespace App\Tests\Functional\Controller\TeamInvite;

use App\Entity\Job\Configuration;
use App\Entity\Team\Invite;
use App\Entity\User;
use App\Repository\ScheduledJobRepository;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Services\Team\InviteService;
use App\Services\Team\MemberService;
use App\Services\Team\Service;
use App\Tests\Factory\UserAccountPlanFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Services\JobConfigurationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group Controller/TeamInviteController
 */
class TeamInviteControllerAcceptActionTest extends AbstractTeamInviteControllerTest
{
    /**
     * @var Invite
     */
    private $invite;

    /**
     * @var User
     */
    private $inviteeUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->inviteeUser = $this->userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee@example.com',
        ]);

        $teamInviteService = self::$container->get(InviteService::class);
        $this->invite = $teamInviteService->get($this->users['leader'], $this->inviteeUser);
    }

    public function testAcceptActionPostRequest()
    {
        $router = self::$container->get('router');
        $requestUrl = $router->generate('teaminvite_accept');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->invite->getUser(),
            'parameters' => [
                'team' => 'Foo',
            ],
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @dataProvider acceptActionClientFailureDataProvider
     *
     * @param string $userName
     * @param array $postData
     * @param array $expectedResponseError
     */
    public function testAcceptActionClientFailure($userName, $postData, $expectedResponseError)
    {
        $user = $this->users[$userName];
        $this->setUser($user);

        $request = new Request([], $postData);
        $response = $this->teamInviteController->acceptAction($request, $user);

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            $expectedResponseError,
            [
                'code' => $response->headers->get('X-TeamInviteAccept-Error-Code'),
                'message' => $response->headers->get('X-TeamInviteAccept-Error-Message'),
            ]
        );
    }

    /**
     * @return array
     */
    public function acceptActionClientFailureDataProvider()
    {
        return [
            'invalid team' => [
                'userName' => 'public',
                'postData' => [
                    'team' => 'invalid-team',
                ],
                'expectedResponseError' => [
                    'code' => 1,
                    'message' => 'Invalid team',
                ],
            ],
            'user not invited to join team' => [
                'userName' => 'public',
                'postData' => [
                    'team' => 'Foo',
                ],
                'expectedResponseError' => [
                    'code' => 2,
                    'message' => 'User has not been invited to join this team',
                ],
            ],
        ];
    }

    public function testAcceptActionUserHasPremiumPlan()
    {
        $teamMemberService = self::$container->get(MemberService::class);

        $userAccountPlanFactory = new UserAccountPlanFactory(self::$container);

        $this->setUser($this->inviteeUser);

        $userAccountPlanFactory->create($this->inviteeUser, 'agency');

        $request = new Request([], [
            'team' => 'Foo',
        ]);
        $response = $this->teamInviteController->acceptAction($request, $this->inviteeUser);

        $this->assertTrue($response->isSuccessful());

        $this->assertFalse($teamMemberService->belongsToTeam($this->inviteeUser));
    }

    public function testAcceptActionSuccess()
    {
        $scheduledJobService = self::$container->get(ScheduledJobService::class);
        $teamMemberService = self::$container->get(MemberService::class);
        $teamService = self::$container->get(Service::class);
        $teamInviteService = self::$container->get(InviteService::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);
        $scheduledJobRepository = self::$container->get(ScheduledJobRepository::class);

        $leader2 = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);

        $teamService->create('Team2', $leader2);
        $teamInviteService->get($leader2, $this->inviteeUser);

        $this->assertCount(2, $teamInviteService->getForUser($this->inviteeUser));

        $jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);
        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->inviteeUser,
            JobConfigurationFactory::KEY_LABEL => 'job-configuration-label',
        ]);

        $scheduledJobService->create($jobConfiguration);

        $scheduledJob = $scheduledJobRepository->findOneBy([
            'jobConfiguration' => $jobConfiguration
        ]);

        $this->assertNotNull($scheduledJob);

        $jobConfiguration = $jobConfigurationRepository->findOneBy([
            'user' => $this->inviteeUser,
        ]);

        $this->assertNotNull($jobConfiguration);

        $this->setUser($this->inviteeUser);

        $request = new Request([], [
            'team' => 'Foo',
        ]);
        $response = $this->teamInviteController->acceptAction($request, $this->inviteeUser);

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($teamMemberService->belongsToTeam($this->inviteeUser));

        $team = $teamService->getForUser($this->inviteeUser);
        $this->assertEquals('Foo', $team->getName());

        $this->assertNull($scheduledJobRepository->findOneBy([
            'jobConfiguration' => $jobConfiguration
        ]));

        $this->assertNull($jobConfigurationRepository->findOneBy([
            'user' => $this->inviteeUser,
        ]));

        $this->assertEmpty($teamInviteService->getForUser($this->inviteeUser));
    }
}
