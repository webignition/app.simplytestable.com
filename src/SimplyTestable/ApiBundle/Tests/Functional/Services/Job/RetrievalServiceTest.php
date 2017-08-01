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
     * @param string $owner
     * @param string $requester
     * @param bool $callSetPublic
     */
    public function testRetrieveSuccess($owner, $requester, $callSetPublic)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $owner = $users[$owner];
        $requester = $users[$requester];

        $jobService = $this->container->get('simplytestable.services.jobservice');

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $owner,
        ]);

        if ($callSetPublic) {
            $job->setIsPublic(true);
            $jobService->persistAndFlush($job);
        }

        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');
        $jobRetrievalService->setUser($requester);

        $retrievedJob = $jobRetrievalService->retrieve($job->getId());
        $this->assertEquals($job, $retrievedJob);
    }

    public function retrieveSuccessDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => false,
            ],
            'private owner, public requester, public job' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => true,
            ],
            'private owner, private requester' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
            ],
            'leader owner, member1 requester' => [
                'owner' => 'leader',
                'requester' => 'member1',
                'callSetPublic' => false,
            ],
            'member1 owner, leader requester' => [
                'owner' => 'member1',
                'requester' => 'leader',
                'callSetPublic' => false,
            ],
            'member1 owner, member2 requester' => [
                'owner' => 'member1',
                'requester' => 'member2',
                'callSetPublic' => false,
            ],
        ];
    }
}
