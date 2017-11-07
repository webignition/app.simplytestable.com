<?php

namespace Tests\ApiBundle\Functional\Services\Job\Configuration;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractConfigurationServiceTest extends AbstractBaseTestCase
{
    /**
     * @var ConfigurationService
     */
    protected $jobConfigurationService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
    }

    /**
     * @param array $jobConfigurationValues
     *
     * @return ConfigurationValues
     */
    protected function createJobConfigurationValuesModel($jobConfigurationValues)
    {
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

        $configurationValuesModel = new ConfigurationValues();

        if (isset($jobConfigurationValues['label'])) {
            $configurationValuesModel->setLabel($jobConfigurationValues['label']);
        }

        if (isset($jobConfigurationValues['website'])) {
            $website = $websiteService->fetch($jobConfigurationValues['website']);
            $configurationValuesModel->setWebsite($website);
        }

        if (isset($jobConfigurationValues['type'])) {
            $jobType = $jobTypeService->get($jobConfigurationValues['type']);
            $configurationValuesModel->setType($jobType);
        }

        if (isset($jobConfigurationValues['task-configuration'])) {
            $taskConfigurationCollection = new TaskConfigurationCollection();

            $taskConfigurationValuesCollection = $jobConfigurationValues['task-configuration'];

            foreach ($taskConfigurationValuesCollection as $taskConfigurationValues) {
                $taskType = $taskTypeService->get($taskConfigurationValues['type']);

                $taskConfiguration = new TaskConfiguration();
                $taskConfiguration->setType($taskType);

                $taskConfigurationCollection->add($taskConfiguration);
            }

            $configurationValuesModel->setTaskConfigurationCollection($taskConfigurationCollection);
        }

        if (isset($jobConfigurationValues['parameters'])) {
            $configurationValuesModel->setParameters($jobConfigurationValues['parameters']);
        }

        return $configurationValuesModel;
    }

    /**
     * @param array $jobConfigurationValuesCollection
     * @param User[] $users
     *
     * @return Configuration[]
     */
    protected function createJobConfigurationCollection($jobConfigurationValuesCollection, $users = [])
    {
        $jobConfigurationCollection = [];

        foreach ($jobConfigurationValuesCollection as $jobConfigurationValues) {
            $jobConfigurationValuesModel = $this->createJobConfigurationValuesModel(
                $jobConfigurationValues
            );

            if (!empty($users)) {
                $currentUser = $users[$jobConfigurationValues['userName']];
                $this->setUser($currentUser);
            }

            $jobConfigurationCollection[] = $this->jobConfigurationService->create($jobConfigurationValuesModel);
        }

        return $jobConfigurationCollection;
    }
}
