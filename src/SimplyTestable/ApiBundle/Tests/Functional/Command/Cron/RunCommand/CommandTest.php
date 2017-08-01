<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Cron\RunCommand;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Command\CommandTest as BaseCommandTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use SimplyTestable\ApiBundle\Command\Cron\RunCommand;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use Cron\CronBundle\Entity\CronReportRepository;
use Cron\CronBundle\Entity\CronReport;

abstract class CommandTest extends BaseCommandTest {

    /**
     * @var int
     */
    protected $returnCode;


    /**
     * @var ScheduledJob
     */
    protected $scheduledJob;


    /**
     * @var CronReportRepository
     */
    protected $cronReportRepository;


    /**
     * @var Job
     */
    protected $job;

    protected function setUp() {
        parent::setUp();

        $this->clearRedis();

        $userFactory = new UserFactory($this->container);
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

        $this->getScheduledJobService()->setUser($user);
        $this->scheduledJob = $this->getScheduledJobService()->create(
            $jobConfiguration,
            $this->getSchedule(),
            $this->getModifier(),
            true
        );

        $this->cronReportRepository = $this->getManager()->getRepository('Cron\CronBundle\Entity\CronReport');

        $this->returnCode = $this->executeCommand($this->getCommandName());
    }

    abstract protected function getExpectedCronJobReturnCode();
    abstract protected function getSchedule();
    abstract protected function getModifier();
    abstract protected function getExpectedCommandOutput();

    /**
     *
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return [
            new RunCommand()
        ];
    }

    public function testCommandReturnCode() {
        $this->assertEquals(0, $this->returnCode);
    }


    public function testCronJobReturnCode() {
        $this->assertEquals($this->getExpectedCronJobReturnCode(), $this->getCronReport()->getExitCode());
    }



    public function testHasCronReport() {
        $this->assertNotNull($this->getCronReport());
    }


    public function testResultantCommandOutput() {
        $this->assertEquals($this->getExpectedCommandOutput(), trim($this->getCronReport()->getOutput()));
    }


    /**
     * @param $rawValues
     * @param User $user
     * @return JobConfiguration
     */
    protected function createJobConfiguration($rawValues, User $user) {
        $jobConfigurationValues = new JobConfigurationValues();

        if (isset($rawValues['label'])) {
            $jobConfigurationValues->setLabel($rawValues['label']);
        }

        if (isset($rawValues['parameters'])) {
            $jobConfigurationValues->setParameters($rawValues['parameters']);
        }

        if (isset($rawValues['type'])) {
            $jobConfigurationValues->setType($this->getJobTypeService()->getByName($rawValues['type']));
        }

        if (isset($rawValues['website'])) {
            $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch($rawValues['website']));
        }

        if (isset($rawValues['task_configuration'])) {
            $taskConfigurationCollection = new TaskConfigurationCollection();

            foreach ($rawValues['task_configuration'] as $taskTypeName => $taskTypeOptions) {
                $taskConfiguration = new TaskConfiguration();
                $taskConfiguration->setType($this->getTaskTypeService()->getByName($taskTypeName));
                $taskConfiguration->setOptions($taskTypeOptions);

                $taskConfigurationCollection->add($taskConfiguration);
            }

            $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        }

        $this->getJobConfigurationService()->setUser($user);
        return $this->getJobConfigurationService()->create($jobConfigurationValues);
    }


    /**
     * @return ScheduledJobService
     */
    protected function getScheduledJobService() {
        return $this->container->get('simplytestable.services.scheduledjob.service');
    }

    /**
     * @return \SimplyTestable\ApiBundle\Services\Job\ConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }


    /**
     * @return CronReport|null
     */
    protected function getCronReport() {
        $reports = $this->cronReportRepository->findBy([
            'job' => $this->scheduledJob->getCronJob()
        ]);

        return (count($reports)) ? $reports[0] : null;
    }

}
