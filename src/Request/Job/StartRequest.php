<?php

namespace App\Request\Job;

use App\Entity\Job\Type as JobType;
use App\Entity\User;
use App\Entity\WebSite;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class StartRequest
{
    private $user;
    private $website;
    private $jobType;
    private $taskConfigurationCollection;
    private $jobParameters;

    public function __construct(
        User $user,
        WebSite $webSite,
        JobType $jobType,
        TaskConfigurationCollection $taskConfigurationCollection,
        array $jobParameters = []
    ) {
        $this->user = $user;
        $this->website = $webSite;
        $this->jobType = $jobType;
        $this->taskConfigurationCollection = $taskConfigurationCollection;
        $this->jobParameters = $jobParameters;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getWebsite(): WebSite
    {
        return $this->website;
    }

    public function getJobType(): JobType
    {
        return $this->jobType;
    }

    public function getTaskConfigurationCollection(): TaskConfigurationCollection
    {
        return $this->taskConfigurationCollection;
    }

    public function getJobParameters(): array
    {
        return $this->jobParameters;
    }
}
