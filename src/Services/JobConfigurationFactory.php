<?php

namespace App\Services;

use App\Entity\Job\Configuration as JobConfiguration;
use App\Request\Job\StartRequest;

class JobConfigurationFactory
{
    public function createFromJobStartRequest(StartRequest $jobStartRequest)
    {
        return JobConfiguration::create(
            '',
            $jobStartRequest->getUser(),
            $jobStartRequest->getWebsite(),
            $jobStartRequest->getJobType(),
            $jobStartRequest->getTaskConfigurationCollection(),
            json_encode($jobStartRequest->getJobParameters())
        );
    }
}
