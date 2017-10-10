<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class ModelFactory
{
    const USER_EMAIL = 'email';
    const WEBSITE_CANONICAL_URL = 'canonical-url';
    const JOB_TYPE_NAME = 'name';
    const TASK_CONFIGURATION_COLLECTION_TYPE = 'type';
    const TASK_CONFIGURATION_COLLECTION_OPTIONS = 'options';
    const TASK_TYPE_NAME = 'name';

    /**
     * @param array $userValues
     *
     * @return User
     */
    public static function createUser($userValues)
    {
        $user = new User();

        $user->setEmail($userValues[self::USER_EMAIL]);
        $user->setEmailCanonical($userValues[self::USER_EMAIL]);

        return $user;
    }

    /**
     * @param array $websiteValues
     *
     * @return WebSite
     */
    public static function createWebsite($websiteValues)
    {
        $website = new WebSite();

        $website->setCanonicalUrl($websiteValues[self::WEBSITE_CANONICAL_URL]);

        return $website;
    }

    /**
     * @param $jobTypeValues
     *
     * @return JobType
     */
    public static function createJobType($jobTypeValues)
    {
        $jobType = new JobType();

        $jobType->setName($jobTypeValues[self::JOB_TYPE_NAME]);

        return $jobType;
    }

    /**
     * @param array $taskConfigurationCollectionValues
     *
     * @return TaskConfigurationCollection
     */
    public static function createTaskConfigurationCollection($taskConfigurationCollectionValues = [])
    {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($taskConfigurationCollectionValues as $taskTypeName => $taskConfigurationValues) {
            $taskType = self::createTaskType([
                self::TASK_TYPE_NAME => $taskTypeName,
            ]);

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);

            if (isset($taskConfigurationValues[self::TASK_CONFIGURATION_COLLECTION_OPTIONS])) {
                $taskConfiguration->setOptions($taskConfigurationValues[self::TASK_CONFIGURATION_COLLECTION_OPTIONS]);
            }

            $taskConfigurationCollection->add($taskConfiguration);
        }

        return $taskConfigurationCollection;
    }

    /**
     * @param array $taskTypeValues
     *
     * @return TaskType
     */
    public static function createTaskType($taskTypeValues)
    {
        $taskType = new TaskType();

        $taskType->setName($taskTypeValues[self::TASK_TYPE_NAME]);

        return $taskType;
    }
}
