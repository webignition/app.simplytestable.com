<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class ModelFactory
{
    const USER_EMAIL = 'email';
    const WEBSITE_CANONICAL_URL = 'canonical-url';
    const JOB_TYPE_NAME = 'name';

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
     * @return TaskConfigurationCollection
     */
    public static function createTaskConfigurationCollection()
    {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        return $taskConfigurationCollection;
    }
}
