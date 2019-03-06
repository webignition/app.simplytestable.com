<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Services\JobAuthorisationService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
use App\Tests\Services\UserFactory;

class JobAuthorisationServiceTest extends AbstractBaseTestCase
{
    /**
     * @var JobAuthorisationService
     */
    private $jobAuthorisationService;

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

        $this->jobAuthorisationService = self::$container->get(JobAuthorisationService::class);
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

        $this->assertEquals($expectedIsAuthorised, $this->jobAuthorisationService->isAuthorised($user, $jobId));
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
}
