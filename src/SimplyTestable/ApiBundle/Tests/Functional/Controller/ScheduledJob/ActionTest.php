<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;

abstract class ActionTest extends BaseControllerJsonTestCase {


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

}