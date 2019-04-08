<?php

namespace App\Tests\Functional\Services\Job\Configuration;

use App\Entity\Job\Configuration;
use App\Entity\Job\TaskConfiguration;
use App\Entity\User;
use App\Model\Job\Configuration\Values as ConfigurationValues;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use App\Services\Job\ConfigurationService;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Services\WebSiteService;
use App\Tests\Functional\AbstractBaseTestCase;

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

        $this->jobConfigurationService = self::$container->get(ConfigurationService::class);
    }

    /**
     * @param array $jobConfigurationValues
     *
     * @return ConfigurationValues
     */
    protected function createJobConfigurationValuesModel($jobConfigurationValues)
    {
        $websiteService = self::$container->get(WebSiteService::class);
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $taskConfigurationCollection = new TaskConfigurationCollection();

        $taskConfigurationValuesCollection = $jobConfigurationValues['task-configuration'];

        foreach ($taskConfigurationValuesCollection as $taskConfigurationValues) {
            $taskType = $taskTypeService->get($taskConfigurationValues['type']);

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        $configurationValuesModel = new ConfigurationValues(
            $jobConfigurationValues['label'],
            $websiteService->get($jobConfigurationValues['website']),
            $jobTypeService->get($jobConfigurationValues['type']),
            $taskConfigurationCollection,
            $jobConfigurationValues['parameters'] ??  null
        );

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
