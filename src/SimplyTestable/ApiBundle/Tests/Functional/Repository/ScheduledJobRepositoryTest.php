<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Repository\ScheduledJobRepository;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\ScheduledJobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

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

        $entityManager = $this->container->get('doctrine.orm.entity_manager');

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
        $scheduledJobFactory = new ScheduledJobFactory($this->container);
        $jobConfigurationFactory = new JobConfigurationFactory($this->container);

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
            'no matches on cron modifier' => [
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
            'matches' => [
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
}
