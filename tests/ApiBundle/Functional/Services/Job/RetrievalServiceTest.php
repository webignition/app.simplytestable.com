<?php

namespace Tests\ApiBundle\Functional\Services\Job\Retrieval;

use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\Team\Service;
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

        $this->userFactory = new UserFactory(self::$container);
        $this->jobFactory = new JobFactory(self::$container);
    }

    /**
     * @dataProvider retrieveFailureDataProvider
     *
     * @param string|null $userTeamStatus
     * @param string|null $jobOwnerTeamStatus
     */
    public function testRetrieveFailure($userTeamStatus, $jobOwnerTeamStatus)
    {
        $jobRetrievalService = self::$container->get(RetrievalService::class);
        $teamService = self::$container->get(Service::class);

        $user = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'user@example.com',
        ]);
        $jobOwner = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'jobowner@example.com',
        ]);

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

        $this->setUser($user);

        $this->expectException(JobRetrievalServiceException::class);
        $this->expectExceptionMessage('Not authorised');
        $this->expectExceptionCode(JobRetrievalServiceException::CODE_NOT_AUTHORISED);

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
        $jobRetrievalService = self::$container->get(RetrievalService::class);

        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $owner = $users[$owner];
        $requester = $users[$requester];

        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $owner,
        ]);

        if ($callSetPublic) {
            $job->setIsPublic(true);

            $entityManager->persist($job);
            $entityManager->flush();
        }

        $this->setUser($requester);

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
