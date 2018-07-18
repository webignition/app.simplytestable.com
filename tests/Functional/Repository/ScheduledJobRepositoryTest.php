<?php

namespace App\Tests\Functional\Services;

use App\Entity\ScheduledJob;
use App\Repository\ScheduledJobRepository;
use App\Tests\Factory\JobConfigurationFactory;
use App\Tests\Factory\ScheduledJobFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;

class ScheduledJobRepositoryTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobRepository
     */
    private $scheduledJobRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $this->scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);
    }

    /**
     * @dataProvider hasDataProvider
     *
     * @param array $scheduledJobValuesCollection
     * @param int $scheduledJobJobConfigurationIndex
     * @param array $jobConfigurationValues
     * @param string $schedule
     * @param string $cronModifier
     * @param bool $isRecurring
     * @param bool $expectedHas
     */
    public function testHas(
        $scheduledJobValuesCollection,
        $scheduledJobJobConfigurationIndex,
        $jobConfigurationValues,
        $schedule,
        $cronModifier,
        $isRecurring,
        $expectedHas
    ) {
        $scheduledJobFactory = new ScheduledJobFactory(self::$container);
        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);

        /* @var ScheduledJob[] $scheduledJobs */
        $scheduledJobs = [];

        foreach ($scheduledJobValuesCollection as $scheduledJobValues) {
            $jobConfiguration = $jobConfigurationFactory->create(
                $scheduledJobValues[ScheduledJobFactory::KEY_JOB_CONFIGURATION]
            );

            $scheduledJobValues[ScheduledJobFactory::KEY_JOB_CONFIGURATION] = $jobConfiguration;

            $scheduledJobs[] = $scheduledJobFactory->create($scheduledJobValues);
        }

        if (is_null($scheduledJobJobConfigurationIndex)) {
            $jobConfiguration = $jobConfigurationFactory->create($jobConfigurationValues);
        } else {
            $scheduledJob = $scheduledJobs[$scheduledJobJobConfigurationIndex];
            $jobConfiguration = $scheduledJob->getJobConfiguration();
        }

        $has = $this->scheduledJobRepository->has($jobConfiguration, $schedule, $cronModifier, $isRecurring);

        $this->assertEquals($expectedHas, $has);
    }

    /**
     * @return array
     */
    public function hasDataProvider()
    {
        return [
            'no scheduled jobs' => [
                'scheduledJobValuesCollection' => [],
                'scheduledJobJobConfigurationIndex' => null,
                'jobConfigurationValues' => [],
                'schedule' => '* * * * *',
                'cronModifier' => null,
                'isRecurring' => true,
                'expectedHas' => false,
            ],
            'no matches on job configuration' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [],
                    ],
                ],
                'scheduledJobJobConfigurationIndex' => null,
                'jobConfigurationValues' => [],
                'schedule' => '* * * * *',
                'cronModifier' => null,
                'isRecurring' => true,
                'expectedHas' => false,
            ],
            'no matches on schedule' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [],
                        ScheduledJobFactory::KEY_SCHEDULE => '* * * * 1',
                        ScheduledJobFactory::KEY_CRON_MODIFIER => null,
                        ScheduledJobFactory::KEY_IS_RECURRING => true,
                    ],
                ],
                'scheduledJobJobConfigurationIndex' => 0,
                'jobConfigurationValues' => null,
                'schedule' => '* * * * *',
                'cronModifier' => null,
                'isRecurring' => true,
                'expectedHas' => false,
            ],
            'no matches on is recurring' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [],
                        ScheduledJobFactory::KEY_SCHEDULE => '* * * * *',
                        ScheduledJobFactory::KEY_CRON_MODIFIER => '[ `date +\%d` -le 0 ]',
                        ScheduledJobFactory::KEY_IS_RECURRING => true,
                    ],
                ],
                'scheduledJobJobConfigurationIndex' => 0,
                'jobConfigurationValues' => null,
                'schedule' => '* * * * *',
                'cronModifier' => '[ `date +\%d` -le 0 ]',
                'isRecurring' => false,
                'expectedHas' => false,
            ],
            'matches with cron modifier set but requested modifier null' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [],
                        ScheduledJobFactory::KEY_SCHEDULE => '* * * * *',
                        ScheduledJobFactory::KEY_CRON_MODIFIER => '[ `date +\%d` -le 0 ]',
                        ScheduledJobFactory::KEY_IS_RECURRING => true,
                    ],
                ],
                'scheduledJobJobConfigurationIndex' => 0,
                'jobConfigurationValues' => null,
                'schedule' => '* * * * *',
                'cronModifier' => null,
                'isRecurring' => true,
                'expectedHas' => true,
            ],
            'matches with cron modifier set and requested modifier set' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [],
                        ScheduledJobFactory::KEY_SCHEDULE => '* * * * *',
                        ScheduledJobFactory::KEY_CRON_MODIFIER => '[ `date +\%d` -le 0 ]',
                        ScheduledJobFactory::KEY_IS_RECURRING => true,
                    ],
                ],
                'scheduledJobJobConfigurationIndex' => 0,
                'jobConfigurationValues' => null,
                'schedule' => '* * * * *',
                'cronModifier' => '[ `date +\%d` -le 0 ]',
                'isRecurring' => true,
                'expectedHas' => true,
            ],
        ];
    }

    /**
     * @dataProvider getListDataProvider
     *
     * @param array $scheduledJobValuesCollection
     * @param string[] $userNames
     * @param int[] $expectedScheduledJobIndices
     */
    public function testGetList(
        $scheduledJobValuesCollection,
        $userNames,
        $expectedScheduledJobIndices
    ) {
        $scheduledJobFactory = new ScheduledJobFactory(self::$container);
        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);
        $userFactory = new UserFactory(self::$container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        /* @var ScheduledJob[] $scheduledJobs */
        $scheduledJobs = [];

        foreach ($scheduledJobValuesCollection as $scheduledJobValues) {
            $jobConfigurationValues = $scheduledJobValues[ScheduledJobFactory::KEY_JOB_CONFIGURATION];

            if (isset($jobConfigurationValues[JobConfigurationFactory::KEY_USER])) {
                $user = $users[$jobConfigurationValues[JobConfigurationFactory::KEY_USER]];
                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $user;
            }

            $jobConfiguration = $jobConfigurationFactory->create(
                $jobConfigurationValues
            );

            $scheduledJobValues[ScheduledJobFactory::KEY_JOB_CONFIGURATION] = $jobConfiguration;

            $scheduledJobs[] = $scheduledJobFactory->create($scheduledJobValues);
        }

        $requestUsers = [];

        foreach ($userNames as $userName) {
            $requestUsers[] = $users[$userName];
        }

        $retrievedScheduledJobs = $this->scheduledJobRepository->getList($requestUsers);

        $this->assertCount(count($expectedScheduledJobIndices), $retrievedScheduledJobs);

        $retrievedScheduledJobIds = [];
        $expectedScheduledJobIds = [];

        foreach ($scheduledJobs as $scheduledJobIndex => $scheduledJob) {
            if (in_array($scheduledJobIndex, $expectedScheduledJobIndices)) {
                $expectedScheduledJobIds[] = $scheduledJob->getId();
            }
        }

        foreach ($retrievedScheduledJobs as $retrievedScheduledJob) {
            $retrievedScheduledJobIds[] = $retrievedScheduledJob->getId();
        }

        $this->assertEquals($expectedScheduledJobIds, $retrievedScheduledJobIds);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        $teamScheduledJobValuesCollection = [
            [
                ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                    JobConfigurationFactory::KEY_USER => 'private',
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
                    JobConfigurationFactory::KEY_USER => 'private',
                ],
            ],
            [
                ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                    JobConfigurationFactory::KEY_USER => 'member2',
                ],
            ],
        ];

        return [
            'no matches' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                            JobConfigurationFactory::KEY_USER => 'private',
                        ],
                    ],
                ],
                'userNames' => [
                    'public',
                ],
                'expectedScheduledJobIndices' => [],
            ],
            'matches for private user' => [
                'scheduledJobValuesCollection' => [
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                            JobConfigurationFactory::KEY_USER => 'private',
                        ],
                    ],
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                            JobConfigurationFactory::KEY_USER => 'leader',
                        ],
                    ],
                    [
                        ScheduledJobFactory::KEY_JOB_CONFIGURATION => [
                            JobConfigurationFactory::KEY_USER => 'private',
                        ],
                    ],
                ],
                'userNames' => [
                    'private',
                ],
                'expectedScheduledJobIndices' => [0, 2],
            ],
            'matches for team leader' => [
                'scheduledJobValuesCollection' => $teamScheduledJobValuesCollection,
                'userNames' => [
                    'leader',
                ],
                'expectedScheduledJobIndices' => [1],
            ],
            'matches for team member1' => [
                'scheduledJobValuesCollection' => $teamScheduledJobValuesCollection,
                'userNames' => [
                    'member1',
                ],
                'expectedScheduledJobIndices' => [2],
            ],
            'matches for team' => [
                'scheduledJobValuesCollection' => $teamScheduledJobValuesCollection,
                'userNames' => [
                    'leader',
                    'member1',
                    'member2',
                ],
                'expectedScheduledJobIndices' => [1, 2, 4],
            ],
        ];
    }
}
