<?php

namespace AppBundle\Request\Job;

use AppBundle\Entity\Job\Type as JobType;
use AppBundle\Entity\User;
use AppBundle\Entity\WebSite;
use AppBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class StartRequest
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var WebSite
     */
    private $website;

    /**
     * @var JobType
     */
    private $jobType;

    /**
     * @var TaskConfigurationCollection
     */
    private $taskConfigurationCollection;

    /**
     * @var array
     */
    private $jobParameters;

    /**
     * @param User $user
     * @param WebSite $webSite
     * @param JobType $jobType
     * @param TaskConfigurationCollection $taskConfigurationCollection
     * @param array $jobParameters
     */
    public function __construct(
        User $user,
        WebSite $webSite,
        JobType $jobType,
        TaskConfigurationCollection $taskConfigurationCollection,
        $jobParameters = []
    ) {
        $this->user = $user;
        $this->website = $webSite;
        $this->jobType = $jobType;
        $this->taskConfigurationCollection = $taskConfigurationCollection;
        $this->jobParameters = $jobParameters;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return WebSite
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return JobType
     */
    public function getJobType()
    {
        return $this->jobType;
    }

    /**
     * @return TaskConfigurationCollection
     */
    public function getTaskConfigurationCollection()
    {
        return $this->taskConfigurationCollection;
    }

    /**
     * @return array
     */
    public function getJobParameters()
    {
        return $this->jobParameters;
    }
}
