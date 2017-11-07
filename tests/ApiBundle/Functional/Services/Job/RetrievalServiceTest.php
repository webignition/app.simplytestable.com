<?php

namespace Tests\ApiBundle\Functional\Services\Job\Retrieval;

use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

class RetrievalServiceTest extends AbstractBaseTestCase
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
        $this->expectException(JobRetrievalServiceException::class);
        $this->expectExceptionMessage('Not authorised');
        $this->expectExceptionCode(JobRetrievalServiceException::CODE_NOT_AUTHORISED);

        $user = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'user@example.com',
        ]);
        $jobOwner = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'jobowner@example.com',
        ]);

        $teamService = $this->container->get('simplytestable.services.teamservice');

        if ($userTeamStatus == 'leader') {
            $teamService->create('userTeamAsLeader', $user);
        } elseif ($userTeamStatus == 'member') {
            $userTeamLeader = $this->userFactory->create([
                UserFactory::KEY_EMAIL => 'userteamleader@example.com',
            ]);
            $userTeam = $teamService->create('userTeamAsMember', $userTeamLeader);
            $teamService->getMemberService()->add($userTeam, $user);
        }

        if ($jobOwnerTeamStatus == 'leader') {
            $teamService->create('ownerTeam', $jobOwner);
        } elseif ($jobOwnerTeamStatus == 'member') {
            $jobOwnerTeamLeader = $this->userFactory->create([
                UserFactory::KEY_EMAIL => 'jobownerteamleader@example.com',
            ]);
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

        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $owner,
        ]);

        if ($callSetPublic) {
            $job->setIsPublic(true);

            $entityManager->persist($job);
            $entityManager->flush();
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
