<?php

namespace Tests\ApiBundle\Functional\Command\Cron;

use Cron\CronBundle\Entity\CronReport;
use SimplyTestable\ApiBundle\Command\Cron\RunCommand;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RunCommandTest extends AbstractBaseTestCase
{
    /**
     * @var RunCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(RunCommand::class);
        $this->command->setContainer(self::$container);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param string $schedule
     * @param string $modifier
     * @param int $expectedCommandReturnCode
     * @param int $expectedCronJobExitCode
     * @param string $expectedCronJobOutput
     */
    public function testRun(
        $schedule,
        $modifier,
        $expectedCommandReturnCode,
        $expectedCronJobExitCode,
        $expectedCronJobOutput
    ) {
        $userFactory = new UserFactory(self::$container);
        $scheduledJobService = self::$container->get(ScheduledJobService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $cronReportRepository = $entityManager->getRepository(CronReport::class);

        $user = $userFactory->createAndActivateUser();

        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $user);

        $scheduledJob = $scheduledJobService->create(
            $jobConfiguration,
            $schedule,
            $modifier,
            true
        );

        $commandReturnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals($expectedCommandReturnCode, $commandReturnCode);

        /* @var CronReport $cronReport */
        $cronReport = $cronReportRepository->findOneBy([
            'job' => $scheduledJob->getCronJob()
        ]);

        $cronJobExitCode = $cronReport->getExitCode();

        $this->assertEquals($expectedCronJobExitCode, $cronJobExitCode);

        $expectedCronJobOutput = str_replace(
            '{{ scheduled_job_id }}',
            $scheduledJob->getId(),
            $expectedCronJobOutput
        );

        $this->assertEquals($expectedCronJobOutput, trim($cronReport->getOutput()));
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'every minute, no modifier, is run' => [
                'schedule' => '* * * * *',
                'modifier' => null,
                'expectedCommandReturnCode' => 0,
                'expectedCronJobExitCode' => 0,
                'expectedCronJobOutput' => implode("\n", [
                    'simplytestable:scheduledjob:enqueue [{{ scheduled_job_id }}] start',
                    'simplytestable:scheduledjob:enqueue [{{ scheduled_job_id }}] done',
                ]),
            ],
            'every minute, exclusive modifier, fails to run' => [
                'schedule' => '* * * * *',
                // Limits execution to days of the month less than or equal to zero, no days match, fails to run
                'modifier' => '[ `date +\%d` -le 0 ]',
                'expectedCommandReturnCode' => 0,
                'expectedCronJobExitCode' => 1,
                'expectedCronJobOutput' => '',
            ],
            'every minute, inclusive modifier, is run' => [
                'schedule' => '* * * * *',
                // Limits execution to days of the month less than or equal to 40, all days match, is run
                'modifier' => '[ `date +\%d` -le 40 ]',
                'expectedCommandReturnCode' => 0,
                'expectedCronJobExitCode' => 0,
                'expectedCronJobOutput' => implode("\n", [
                    'simplytestable:scheduledjob:enqueue [{{ scheduled_job_id }}] start',
                    'simplytestable:scheduledjob:enqueue [{{ scheduled_job_id }}] done',
                ]),
            ],
        ];
    }

    /**
     * @param array $rawValues
     * @param User $user
     *
     * @return JobConfiguration
     */
    private function createJobConfiguration($rawValues, User $user)
    {
        $jobConfigurationService = self::$container->get(ConfigurationService::class);
        $websiteService = self::$container->get(WebSiteService::class);
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $jobConfigurationValues = new JobConfigurationValues();

        if (isset($rawValues['label'])) {
            $jobConfigurationValues->setLabel($rawValues['label']);
        }

        if (isset($rawValues['parameters'])) {
            $jobConfigurationValues->setParameters($rawValues['parameters']);
        }

        if (isset($rawValues['type'])) {
            $jobType = $jobTypeService->get($rawValues['type']);
            $jobConfigurationValues->setType($jobType);
        }

        if (isset($rawValues['website'])) {
            $jobConfigurationValues->setWebsite($websiteService->get($rawValues['website']));
        }

        if (isset($rawValues['task_configuration'])) {
            $taskConfigurationCollection = new TaskConfigurationCollection();

            foreach ($rawValues['task_configuration'] as $taskTypeName => $taskTypeOptions) {
                $taskConfiguration = new TaskConfiguration();
                $taskConfiguration->setType($taskTypeService->get($taskTypeName));
                $taskConfiguration->setOptions($taskTypeOptions);

                $taskConfigurationCollection->add($taskConfiguration);
            }

            $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        }

        $this->setUser($user);

        return $jobConfigurationService->create($jobConfigurationValues);
    }
}
