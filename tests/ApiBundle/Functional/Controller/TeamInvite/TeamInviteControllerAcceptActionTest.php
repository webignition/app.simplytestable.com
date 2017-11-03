<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\Team\Invite;
use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\JobConfigurationFactory;
use Tests\ApiBundle\Factory\UserAccountPlanFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;

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

        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $this->invite = $teamInviteService->get($this->users['leader'], $this->inviteeUser);
    }

    public function testAcceptActionPostRequest()
    {
        $router = $this->container->get('router');
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
        $response = $this->teamInviteController->acceptAction($request);

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
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');

        $userAccountPlanFactory = new UserAccountPlanFactory($this->container);

        $this->setUser($this->inviteeUser);

        $userAccountPlanFactory->create($this->inviteeUser, 'agency');

        $request = new Request([], [
            'team' => 'Foo',
        ]);
        $response = $this->teamInviteController->acceptAction($request);

        $this->assertTrue($response->isSuccessful());

        $this->assertFalse($teamMemberService->belongsToTeam($this->inviteeUser));
    }

    public function testAcceptActionSuccess()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $leader2 = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'leader2@example.com',
        ]);

        $team2 = $teamService->create('Team2', $leader2);
        $teamInviteService->get($leader2, $this->inviteeUser);

        $this->assertCount(2, $teamInviteService->getForUser($this->inviteeUser));

        $scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);
        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
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
        $response = $this->teamInviteController->acceptAction($request);

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
