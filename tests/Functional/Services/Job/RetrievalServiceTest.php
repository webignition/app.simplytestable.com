<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\Job\Retrieval;

use App\Entity\User;
use App\Services\Job\RetrievalService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use App\Tests\Services\JobFactory;

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
     * @var RetrievalService
     */
    private $retrievalService;

    /**
     * @var User[]
     */
    private $users;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = self::$container->get(UserFactory::class);
        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->retrievalService = self::$container->get(RetrievalService::class);

        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }

    /**
     * @dataProvider retrieveFailureDataProvider
     */
    public function testRetrieveFailure(string $ownerName, string $userName)
    {
        $owner = $this->users[$ownerName];
        $user = $this->users[$userName];

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $owner,
        ]);

        $this->setUser($user);

        $this->expectException(JobRetrievalServiceException::class);
        $this->expectExceptionMessage('Not authorised');
        $this->expectExceptionCode(JobRetrievalServiceException::CODE_NOT_AUTHORISED);

        $this->retrievalService->retrieve($job->getId());
    }

    public function retrieveFailureDataProvider(): array
    {
        return [
            'owner=private, user=public' => [
                'ownerName' => 'private',
                'userName' => 'public',
            ],
            'owner=private, user=leader' => [
                'ownerName' => 'private',
                'userName' => 'leader',
            ],
            'owner=leader, user=private' => [
                'ownerName' => 'leader',
                'userName' => 'private',
            ],
            'owner=member1, user=private' => [
                'ownerName' => 'member1',
                'userName' => 'private',
            ],
        ];
    }

    /**
     * @dataProvider retrieveSuccessDataProvider
     */
    public function testRetrieveSuccess(string $ownerName, string $userName, bool $jobIsPublic)
    {
        $owner = $this->users[$ownerName];
        $user = $this->users[$userName];

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $owner,
            JobFactory::KEY_SET_PUBLIC => $jobIsPublic,
        ]);

        $this->setUser($user);

        $retrievedJob = $this->retrievalService->retrieve($job->getId());

        $this->assertEquals($job, $retrievedJob);
    }

    public function retrieveSuccessDataProvider(): array
    {
        return [
            'owner=public, user=public, jobIsPublic=true' => [
                'ownerName' => 'public',
                'userName' => 'public',
                'jobIsPublic' => true,
            ],
            'owner=private, user=public, jobIsPublic=true' => [
                'ownerName' => 'private',
                'userName' => 'public',
                'jobIsPublic' => true,
            ],
            'owner=member1, user=private, jobIsPublic=true' => [
                'ownerName' => 'member1',
                'userName' => 'private',
                'jobIsPublic' => true,
            ],
            'owner=private, user=private, jobIsPublic=false' => [
                'ownerName' => 'private',
                'userName' => 'private',
                'jobIsPublic' => false,
            ],
            'owner=leader, user=member1, jobIsPublic=false' => [
                'ownerName' => 'leader',
                'userName' => 'member1',
                'jobIsPublic' => false,
            ],
            'owner=member1, user=leader, jobIsPublic=false' => [
                'ownerName' => 'member1',
                'userName' => 'leader',
                'jobIsPublic' => false,
            ],
            'owner=member1, user=member2, jobIsPublic=false' => [
                'ownerName' => 'member1',
                'userName' => 'member2',
                'jobIsPublic' => false,
            ],
        ];
    }
}
