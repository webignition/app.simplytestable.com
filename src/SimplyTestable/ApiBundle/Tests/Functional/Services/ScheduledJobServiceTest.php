<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use Cron\CronBundle\Entity\CronJob;
use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobServiceException;
use SimplyTestable\ApiBundle\Repository\ScheduledJobRepository;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\ScheduledJobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use Cron\CronBundle\Cron\Manager as CronManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScheduledJobServiceTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobService
     */
    private $scheduledJobService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
    }

    public function testCreateMatchingScheduledJobExists()
    {
        $scheduledJobRepository = \Mockery::mock(ScheduledJobRepository::class);
        $scheduledJobRepository
            ->shouldReceive('has')
            ->andReturn(true);

        /* @var EntityManager $entityManager */
        $entityManager = \Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('getRepository')
            ->with(ScheduledJob::class)
            ->andReturn($scheduledJobRepository);

        /* @var JobConfigurationService $jobConfigurationService */
        $jobConfigurationService = \Mockery::mock(JobConfigurationService::class);

        /* @var TeamService $teamService */
        $teamService = \Mockery::mock(TeamService::class);

        /* @var CronManager $cronManager */
        $cronManager = \Mockery::mock(CronManager::class);

        /* @var TokenStorageInterface $tokenStorage */
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);

        $scheduledJobService = new ScheduledJobService(
            $entityManager,
            $jobConfigurationService,
            $teamService,
            $cronManager,
            $tokenStorage
        );

        /* @var JobConfiguration $jobConfiguration */
        $jobConfiguration = \Mockery::mock(JobConfiguration::class);

        $this->expectException(ScheduledJobServiceException::class);
        $this->expectExceptionMessage('Matching scheduled job exists');
        $this->expectExceptionCode(ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS);

        $scheduledJobService->create($jobConfiguration);
    }

    /**
     * @dataProvider createSuccessDataProvider
     *
     * @param array $jobConfigurationValues
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     * @param string $expectedCronJobCommand
     */
    public function testCreateSuccess(
        $jobConfigurationValues,
        $schedule,
        $cronModifier,
        $isRecurring,
        $expectedCronJobCommand
    ) {
        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfiguration = $jobConfigurationFactory->create($jobConfigurationValues);

        $scheduledJob = $this->scheduledJobService->create($jobConfiguration, $schedule, $cronModifier, $isRecurring);

        $this->assertInstanceOf(ScheduledJob::class, $scheduledJob);
        $this->assertNotNull($scheduledJob->getId());
        $this->assertEquals($isRecurring, $scheduledJob->getIsRecurring());
        $this->assertEquals($cronModifier, $scheduledJob->getCronModifier());

        $cronJob = $scheduledJob->getCronJob();
        $this->assertInstanceOf(CronJob::class, $cronJob);
        $this->assertNotNull($cronJob->getId());
        $this->assertEquals($schedule, $cronJob->getSchedule());
        $this->assertEquals(
            sprintf($expectedCronJobCommand, $scheduledJob->getId()),
            $cronJob->getCommand()
        );
    }

    /**
     * @return array
     */
    public function createSuccessDataProvider()
    {
        return [
            'without cron modifier' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                ],
                'schedule' => '* * * * * *',
                'cronModifier' => null,
                'isRecurring' => true,
                'expectedCronJobCommand' => 'simplytestable:scheduledjob:enqueue %s'
            ],
            'with cron modifier' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                ],
                'schedule' => '* * * * * *',
                'cronModifier' => 'bar',
                'isRecurring' => true,
                'expectedCronJobCommand' => 'simplytestable:scheduledjob:enqueue %s #bar'
            ],
        ];
    }

    public function testGetInvalidId()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $scheduledJob = $this->scheduledJobService->get(0);

        $this->assertNull($scheduledJob);
    }

    public function testGetSuccessForNonTeamUser()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $scheduledJobFactory = new ScheduledJobFactory($this->container);

        $scheduledJob = $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfigurationFactory->create([
                JobConfigurationFactory::KEY_USER => $user,
            ]),
        ]);

        $retrievedScheduledJob = $this->scheduledJobService->get($scheduledJob->getId());

        $this->assertEquals($scheduledJob, $retrievedScheduledJob);
    }

    public function testGetFailureForNonTeamUser()
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $scheduledJobFactory = new ScheduledJobFactory($this->container);

        $scheduledJob = $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfigurationFactory->create([
                JobConfigurationFactory::KEY_USER => $users['public'],
            ]),
        ]);

        $this->setUser($users['private']);

        $retrievedScheduledJob = $this->scheduledJobService->get($scheduledJob->getId());

        $this->assertNull($retrievedScheduledJob);
    }

    public function testGetForTeamUsers()
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $scheduledJobFactory = new ScheduledJobFactory($this->container);

        $leaderScheduledJob = $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfigurationFactory->create([
                JobConfigurationFactory::KEY_USER => $users['leader'],
            ]),
        ]);

        $memberScheduledJob = $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfigurationFactory->create([
                JobConfigurationFactory::KEY_USER => $users['member1'],
            ]),
        ]);

        $this->setUser($users['leader']);

        $this->assertEquals($leaderScheduledJob, $this->scheduledJobService->get($leaderScheduledJob->getId()));
        $this->assertEquals($memberScheduledJob, $this->scheduledJobService->get($memberScheduledJob->getId()));

        $this->setUser($users['member1']);

        $this->assertEquals($leaderScheduledJob, $this->scheduledJobService->get($leaderScheduledJob->getId()));
        $this->assertEquals($memberScheduledJob, $this->scheduledJobService->get($memberScheduledJob->getId()));
    }

    public function testGetFailureForTeamUsers()
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $scheduledJobFactory = new ScheduledJobFactory($this->container);

        $scheduledJob = $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfigurationFactory->create([
                JobConfigurationFactory::KEY_USER => $users['private'],
            ]),
        ]);

        $this->setUser($users['leader']);

        $this->assertNull($this->scheduledJobService->get($scheduledJob->getId()));

        $this->setUser($users['member1']);

        $this->assertNull($this->scheduledJobService->get($scheduledJob->getId()));
    }

    /**
     * @dataProvider getListDataProvider
     *
     * @param array $scheduledJobValuesCollection
     * @param string $userName
     * @param int[] $expectedScheduledJobIndices
     */
    public function testGetList($scheduledJobValuesCollection, $userName, $expectedScheduledJobIndices)
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        /* @var ScheduledJob[] $scheduledJobs */
        $scheduledJobs = [];

        if (!empty($scheduledJobValuesCollection)) {
            $scheduledJobFactory = new ScheduledJobFactory($this->container);
            $jobConfigurationFactory = new JobConfigurationFactory($this->container);

            foreach ($scheduledJobValuesCollection as $scheduledJobValues) {
                $jobConfigurationValues = $scheduledJobValues[ScheduledJobFactory::KEY_JOB_CONFIGURATION];

                $jobConfigurationUserName = $jobConfigurationValues[JobConfigurationFactory::KEY_USER];
                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $users[$jobConfigurationUserName];

                $jobConfiguration = $jobConfigurationFactory->create($jobConfigurationValues);

                $scheduledJobValues[ScheduledJobFactory::KEY_JOB_CONFIGURATION] = $jobConfiguration;

                $scheduledJobs[] = $scheduledJobFactory->create($scheduledJobValues);
            }
        }

        $this->setUser($user);

        $scheduledJobList = $this->scheduledJobService->getList();

        $this->assertCount(count($expectedScheduledJobIndices), $scheduledJobList);

        $scheduledJobListIds = [];
        $expectedScheduledJobIds = [];

        foreach ($scheduledJobs as $scheduledJobIndex => $scheduledJob) {
            if (in_array($scheduledJobIndex, $expectedScheduledJobIndices)) {
                $expectedScheduledJobIds[] = $scheduledJob->getId();
            }
        }

        foreach ($scheduledJobList as $scheduledJob) {
            $scheduledJobListIds[] = $scheduledJob->getId();
        }

        $this->assertEquals($expectedScheduledJobIds, $scheduledJobListIds);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        $teamScheduledJobValuesCollection = [
            [
                ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                    JobConfigurationFactory::KEY_USER => 'public',
                ],
            ],
            [
                ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                    JobConfigurationFactory::KEY_USER => 'leader',
                ],
            ],
            [
                ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                    JobConfigurationFactory::KEY_USER => 'member1',
                ],
            ],
            [
                ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                    JobConfigurationFactory::KEY_USER => 'member2',
                ],
            ],
            [
                ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                    JobConfigurationFactory::KEY_USER => 'private',
                ],
            ],
        ];

        return [
            'private user, no scheduled jobs' => [
                'scheduledJobValuesCollection' => [],
                'userName' => 'private',
                'expectedScheduledJobIndices' => [],
            ],
            'private user, has scheduled jobs' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                            JobConfigurationFactory::KEY_USER => 'public',
                        ],
                    ],
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                            JobConfigurationFactory::KEY_USER => 'private',
                        ],
                    ],
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                            JobConfigurationFactory::KEY_USER => 'private',
                        ],
                    ],
                ],
                'userName' => 'private',
                'expectedScheduledJobIndices' => [1, 2],
            ],
            'team leader' => [
                'scheduledJobValuesCollection' => $teamScheduledJobValuesCollection,
                'userName' => 'leader',
                'expectedScheduledJobIndices' => [1, 2, 3],
            ],
            'team member' => [
                'scheduledJobValuesCollection' => $teamScheduledJobValuesCollection,
                'userName' => 'member1',
                'expectedScheduledJobIndices' => [1, 2, 3],
            ],
        ];
    }

    public function testDelete()
    {
        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $scheduledJobFactory = new ScheduledJobFactory($this->container);

        $scheduledJob = $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfigurationFactory->create(),
        ]);

        $this->assertInstanceOf(ScheduledJob::class, $scheduledJob);
        $this->assertNotNull($scheduledJob->getId());
        $this->assertNotNull($scheduledJob->getCronJob()->getId());

        $this->scheduledJobService->delete($scheduledJob);

        $this->assertNull($scheduledJob->getId());
        $this->assertNull($scheduledJob->getCronJob()->getId());
    }

    public function testUpdateHasMatchingScheduledJob()
    {
        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $scheduledJobFactory = new ScheduledJobFactory($this->container);

        $jobConfiguration = $jobConfigurationFactory->create();

        $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfiguration,
            ScheduledJobFactory::KEY_SCHEDULE => '* * * * *',
        ]);

        $scheduledJob = $scheduledJobFactory->create([
            ScheduledJobFactory::KEY_JOB_CONFIGURATION => $jobConfiguration,
            ScheduledJobFactory::KEY_SCHEDULE => '* * * * 1',
        ]);

        $this->expectException(ScheduledJobServiceException::class);
        $this->expectExceptionMessage('Matching scheduled job exists');
        $this->expectExceptionCode(ScheduledJobServiceException::CODE_MATCHING_SCHEDULED_JOB_EXISTS);

        $this->scheduledJobService->update(
            $scheduledJob,
            null,
            '* * * * *',
            null,
            null
        );
    }

    /**
     * @dataProvider updateSuccessDataProvider
     *
     * @param array $jobConfigurationValues
     * @param array $scheduledJobValues
     * @param array $updatedJobConfigurationValues
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     * @param $expectedJobConfigurationLabel
     */
    public function testUpdateSuccess(
        $jobConfigurationValues,
        $scheduledJobValues,
        $updatedJobConfigurationValues,
        $schedule,
        $cronModifier,
        $isRecurring,
        $expectedJobConfigurationLabel
    ) {
        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $scheduledJobFactory = new ScheduledJobFactory($this->container);

        $jobConfiguration = $jobConfigurationFactory->create($jobConfigurationValues);

        $scheduledJobValues[ScheduledJobFactory::KEY_JOB_CONFIGURATION] = $jobConfiguration;

        $scheduledJob = $scheduledJobFactory->create($scheduledJobValues);

        $updatedJobConfiguration = null;

        if (is_array($updatedJobConfigurationValues)) {
            $updatedJobConfiguration = $jobConfigurationFactory->create($updatedJobConfigurationValues);
        } elseif ($updatedJobConfigurationValues === true) {
            $updatedJobConfiguration = $jobConfiguration;
        }

        $this->scheduledJobService->update(
            $scheduledJob,
            $updatedJobConfiguration,
            $schedule,
            $cronModifier,
            $isRecurring
        );

        $this->assertEquals($schedule, $scheduledJob->getCronJob()->getSchedule());
        $this->assertEquals($cronModifier, $scheduledJob->getCronModifier());
        $this->assertEquals($isRecurring, $scheduledJob->getIsRecurring());
        $this->assertEquals($expectedJobConfigurationLabel, $scheduledJob->getJobConfiguration()->getLabel());
    }

    /**
     * @return array
     */
    public function updateSuccessDataProvider()
    {
        return [
            'no changes, null job configuration' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                ],
                'scheduledJobValues' => [
                    ScheduledJobFactory::KEY_SCHEDULE => '* * * * *',
                    ScheduledJobFactory::KEY_CRON_MODIFIER => null,
                    ScheduledJobFactory::KEY_IS_RECURRING => true,
                ],
                'updatedJobConfigurationValues' => null,
                'schedule' => '* * * * *',
                'cronModifier' => null,
                'isRecurring' =>  true,
                'expectedJobConfigurationLabel' => 'foo',
            ],
            'no changes, same job configuration' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                ],
                'scheduledJobValues' => [
                    ScheduledJobFactory::KEY_SCHEDULE => '* * * * *',
                    ScheduledJobFactory::KEY_CRON_MODIFIER => 'bar',
                    ScheduledJobFactory::KEY_IS_RECURRING => true,
                ],
                'updatedJobConfigurationValues' => true,
                'schedule' => '* * * * *',
                'cronModifier' => 'bar',
                'isRecurring' =>  true,
                'expectedJobConfigurationLabel' => 'foo',
            ],
            'update job configuration, schedule' => [
                'jobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foo',
                ],
                'scheduledJobValues' => [
                    ScheduledJobFactory::KEY_SCHEDULE => '* * * * *',
                    ScheduledJobFactory::KEY_CRON_MODIFIER => 'bar',
                    ScheduledJobFactory::KEY_IS_RECURRING => true,
                ],
                'updatedJobConfigurationValues' => [
                    JobConfigurationFactory::KEY_LABEL => 'foobar',
                ],
                'schedule' => '* * * * 1',
                'cronModifier' => 'updated cron modifier',
                'isRecurring' =>  false,
                'expectedJobConfigurationLabel' => 'foobar',
            ],
        ];
    }
}
