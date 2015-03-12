<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\Create;

use SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\ServiceTest as BaseServiceTest;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\User;

abstract class ServiceTest extends BaseServiceTest {

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
