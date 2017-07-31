<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Retrieval;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

class RetrievalServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }

    /**
     * @dataProvider retrieveFailureDataProvider
     *
     * @param string|null $userTeamStatus
     * @param string|null $jobOwnerTeamStatus
     */
    public function testRetrieveFailure($userTeamStatus, $jobOwnerTeamStatus)
    {
        $this->setExpectedException(
            JobRetrievalServiceException::class,
            'Not authorised',
            JobRetrievalServiceException::CODE_NOT_AUTHORISED
        );

        $user = $this->userFactory->create('user@example.com');
        $jobOwner = $this->userFactory->create('jobowner@example.com');

        $teamService = $this->container->get('simplytestable.services.teamservice');

        if ($userTeamStatus == 'leader') {
            $teamService->create('userTeamAsLeader', $user);
        } elseif ($userTeamStatus == 'member') {
            $userTeamLeader = $this->userFactory->create('userteamleader@example.com');
            $userTeam = $teamService->create('userTeamAsMember', $userTeamLeader);
            $teamService->getMemberService()->add($userTeam, $user);
        }

        if ($jobOwnerTeamStatus == 'leader') {
            $teamService->create('ownerTeam', $jobOwner);
        } elseif ($jobOwnerTeamStatus == 'member') {
            $jobOwnerTeamLeader = $this->userFactory->create('jobownerteamleader@example.com');
            $ownerTeam = $teamService->create('ownerTeamAsMember', $jobOwnerTeamLeader);
            $teamService->getMemberService()->add($ownerTeam, $jobOwner);
        }

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $jobOwner,
        ]);

        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');
        $jobRetrievalService->setUser($user);

        $jobRetrievalService->retrieve($job->getId());
    }

    /**
     * @return array
     */
    public function retrieveFailureDataProvider()
    {
        return [
            'user not in a team, owner not in a team' => [
                'userTeamStatus' => null,
                'jobOwnerTeamStatus' => null,
            ],
            'user not in a team, owner is team leader' => [
                'userTeamStatus' => null,
                'jobOwnerTeamStatus' => 'leader',
            ],
            'user not in a team, owner is team member' => [
                'userTeamStatus' => null,
                'jobOwnerTeamStatus' => 'member',
            ],
            'user is team leader, owner is different team leader' => [
                'userTeamStatus' => 'leader',
                'jobOwnerTeamStatus' => 'member',
            ],
            'user is team leader, owner is different team member' => [
                'userTeamStatus' => 'leader',
                'jobOwnerTeamStatus' => 'member',
            ],
            'user is team member, owner is different team leader' => [
                'userTeamStatus' => 'member',
                'jobOwnerTeamStatus' => 'leader',
            ],
            'user is team member, owner is different team member' => [
                'userTeamStatus' => 'member',
                'jobOwnerTeamStatus' => 'leader',
            ],
        ];
    }

    /**
     * @dataProvider retrieveSuccessDataProvider
     *
     * @param string $userEmail
     * @param string|null $userTeamStatus
     * @param string $jobOwnerEmail
     * @param string|null $jobOwnerTeamStatus
     * @param bool $isJobPublic
     */
    public function testRetrieveSuccess(
        $userEmail,
        $userTeamStatus,
        $jobOwnerEmail,
        $jobOwnerTeamStatus,
        $isJobPublic
    ) {
        $user = $this->userFactory->create($userEmail);
        $jobOwner = $this->userFactory->create($jobOwnerEmail);

        $teamService = $this->container->get('simplytestable.services.teamservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');

        if ($userTeamStatus == 'leader' || $jobOwnerTeamStatus == 'leader') {
            $team = $teamService->create(
                'team',
                $userTeamStatus == 'leader' ? $user : $jobOwner
            );

            if ($userTeamStatus == 'member' || $jobOwnerTeamStatus == 'member') {
                $teamService->getMemberService()->add(
                    $team,
                    $userTeamStatus == 'member' ? $user : $jobOwner
                );
            }
        } elseif ($userTeamStatus == 'member' && $jobOwnerTeamStatus == 'member') {
            $leader = $this->userFactory->create('teamleader@example.com');

            $team = $teamService->create('team', $leader);
            $teamService->getMemberService()->add($team, $user);
            $teamService->getMemberService()->add($team, $jobOwner);
        }

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $jobOwner,
        ]);

        if ($isJobPublic) {
            $job->setIsPublic(true);
            $jobService->persistAndFlush($job);
        }

        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');
        $jobRetrievalService->setUser($user);

        $retrievedJob = $jobRetrievalService->retrieve($job->getId());
        $this->assertEquals($job, $retrievedJob);
    }

    public function retrieveSuccessDataProvider()
    {
        return [
            'public job' => [
                'userEmail' => 'user@example.com',
                'userTeamStatus' => null,
                'jobOwnerEmail' => 'jobowner@example.com',
                'jobOwnerTeamStatus' => null,
                'isJobPublic' => true,
            ],
            'user is owner' => [
                'userEmail' => 'user@example.com',
                'userTeamStatus' => null,
                'jobOwnerEmail' => 'user@example.com',
                'jobOwnerTeamStatus' => null,
                'isJobPublic' => false,
            ],
            'user member of team, owner leader of team' => [
                'userEmail' => 'user@example.com',
                'userTeamStatus' => 'member',
                'jobOwnerEmail' => 'teamleader@example.com',
                'jobOwnerTeamStatus' => 'leader',
                'isJobPublic' => false,
            ],
            'user leader of team, owner member of team' => [
                'userEmail' => 'user@example.com',
                'userTeamStatus' => 'leader',
                'jobOwnerEmail' => 'teammember@example.com',
                'jobOwnerTeamStatus' => 'member',
                'isJobPublic' => false,
            ],
            'user member of team, owner member of team' => [
                'userEmail' => 'user@example.com',
                'userTeamStatus' => 'member',
                'jobOwnerEmail' => 'teammember@example.com',
                'jobOwnerTeamStatus' => 'member',
                'isJobPublic' => false,
            ],
        ];
    }
}
