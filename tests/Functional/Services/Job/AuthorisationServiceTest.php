<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\Job;

use App\Services\Job\AuthorisationService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
use App\Tests\Services\UserFactory;

class AuthorisationServiceTest extends AbstractBaseTestCase
{
    /**
     * @var AuthorisationService
     */
    private $authorisationService;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->authorisationService = self::$container->get(AuthorisationService::class);
        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->userFactory = self::$container->get(UserFactory::class);
    }

    /**
     * @dataProvider isAuthorisedDataProvider
     */
    public function testIsAuthorised(string $ownerName, string $userName, bool $expectedIsAuthorised)
    {
        $allUsers = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $owner = $allUsers[$ownerName];
        $user = $allUsers[$userName];

        $jobValues = [
            JobFactory::KEY_USER => $owner,
        ];

        $job = $this->jobFactory->create($jobValues);
        $jobId = $job->getId();

        $this->setUser($user);

        $this->assertEquals($expectedIsAuthorised, $this->authorisationService->isAuthorised($jobId));
    }

    public function isAuthorisedDataProvider(): array
    {
        return [
            'owner=public; user=public' => [
                'ownerName' => 'public',
                'userName' => 'public',
                'expectedIsAuthorised' => true,
            ],
            'owner=leader; user=leader' => [
                'ownerName' => 'leader',
                'userName' => 'leader',
                'expectedIsAuthorised' => true,
            ],
            'owner=member1; user=member1' => [
                'ownerName' => 'member1',
                'userName' => 'member1',
                'expectedIsAuthorised' => true,
            ],
            'owner=leader; user=member1' => [
                'ownerName' => 'leader',
                'userName' => 'member1',
                'expectedIsAuthorised' => true,
            ],
            'owner=member1; user=leader' => [
                'ownerName' => 'member1',
                'userName' => 'leader',
                'expectedIsAuthorised' => true,
            ],
            'owner=public; user=leader' => [
                'ownerName' => 'public',
                'userName' => 'leader',
                'expectedIsAuthorised' => true,
            ],
            'owner=public; user=member1' => [
                'ownerName' => 'public',
                'userName' => 'member1',
                'expectedIsAuthorised' => true,
            ],
            'owner=leader; user=public' => [
                'ownerName' => 'leader',
                'userName' => 'public',
                'expectedIsAuthorised' => false,
            ],
        ];
    }

    public function testIsAuthorisedPublicJob()
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $publicUserPublicJob = $this->jobFactory->create([
            JobFactory::KEY_USER => $users['public'],
            JobFactory::KEY_SET_PUBLIC => true,
        ]);

        $privateUserPublicJob = $this->jobFactory->create([
            JobFactory::KEY_USER => $users['private'],
            JobFactory::KEY_SET_PUBLIC => true,
        ]);

        $publicUserPublicJobId = $publicUserPublicJob->getId();
        $privateUserPublicJobId = $privateUserPublicJob->getId();

        $this->setUser($users['public']);
        $this->assertTrue($this->authorisationService->isAuthorised($publicUserPublicJobId));
        $this->assertTrue($this->authorisationService->isAuthorised($privateUserPublicJobId));

        $this->setUser($users['private']);
        $this->assertTrue($this->authorisationService->isAuthorised($publicUserPublicJobId));
        $this->assertTrue($this->authorisationService->isAuthorised($privateUserPublicJobId));

        $this->setUser($users['leader']);
        $this->assertTrue($this->authorisationService->isAuthorised($publicUserPublicJobId));
        $this->assertTrue($this->authorisationService->isAuthorised($privateUserPublicJobId));
    }
}
