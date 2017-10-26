<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration as TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\User;

abstract class ServiceTest extends BaseSimplyTestableTestCase {

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
    protected function createJobConfiguration($rawValues, User $user)
    {
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $jobConfigurationValues = new JobConfigurationValues();

        if (isset($rawValues['label'])) {
            $jobConfigurationValues->setLabel($rawValues['label']);
        }

        if (isset($rawValues['parameters'])) {
            $jobConfigurationValues->setParameters($rawValues['parameters']);
        }

        if (isset($rawValues['type'])) {
            $jobConfigurationValues->setType($jobTypeService->getByName($rawValues['type']));
        }

        if (isset($rawValues['website'])) {
            $jobConfigurationValues->setWebsite($websiteService->fetch($rawValues['website']));
        }

        if (isset($rawValues['task_configuration'])) {
            $taskConfigurationCollection = new TaskConfigurationCollection();

            foreach ($rawValues['task_configuration'] as $taskTypeName => $taskTypeOptions) {
                $taskConfiguration = new TaskConfiguration();
                $taskConfiguration->setType($taskTypeService->getByName($taskTypeName));
                $taskConfiguration->setOptions($taskTypeOptions);

                $taskConfigurationCollection->add($taskConfiguration);
            }

            $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        }

        $this->getJobConfigurationService()->setUser($user);
        return $this->getJobConfigurationService()->create($jobConfigurationValues);
    }

}
