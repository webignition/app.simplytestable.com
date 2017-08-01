<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Tests\Functional\Command\CommandTest as BaseCommandTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\User;

abstract class CommandTest extends BaseCommandTest {

    /**
     * @var int
     */
    protected $returnCode;


    /**
     * @var Job
     */
    protected $job;

    protected function setUp() {
        parent::setUp();

        $this->clearRedis();
        $this->preCall();

        $this->returnCode = $this->executeCommand($this->getCommandName(), [
            'id' => $this->getScheduledJobId()
        ]);
    }

    abstract protected function getScheduledJobId();
    abstract protected function getExpectedReturnCode();

    protected function preCall() {}

    /**
     *
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return [
            new ExecuteCommand()
        ];
    }

    public function testReturnCode() {
        $this->assertEquals($this->getExpectedReturnCode(), $this->returnCode);
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Job\ConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\ScheduledJob\Service
     */
    protected function getScheduledJobService() {
        return $this->container->get('simplytestable.services.scheduledjob.service');
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

                if (isset($taskTypeOptions['is-enabled'])) {
                    $taskConfiguration->setIsEnabled(filter_var($taskTypeOptions['is-enabled'], FILTER_VALIDATE_BOOLEAN));

                    unset($taskTypeOptions['is-enabled']);
                }

                $taskConfiguration->setOptions($taskTypeOptions);



                $taskConfigurationCollection->add($taskConfiguration);
            }

            $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        }

        $this->getJobConfigurationService()->setUser($user);
        return $this->getJobConfigurationService()->create($jobConfigurationValues);
    }

}
