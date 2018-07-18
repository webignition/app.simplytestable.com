<?php

namespace AppBundle\Services;

use AppBundle\Entity\Job\Configuration as JobConfiguration;
use AppBundle\Request\Job\StartRequest;

class JobConfigurationFactory
{
    public function createFromJobStartRequest(StartRequest $jobStartRequest)
    {
        $jobConfiguration = new JobConfiguration();

        $jobConfiguration->setUser($jobStartRequest->getUser());
        $jobConfiguration->setType($jobStartRequest->getJobType());
        $jobConfiguration->setWebsite($jobStartRequest->getWebsite());

        $jobParameters = $jobStartRequest->getJobParameters();
        if (!empty($jobParameters)) {
            $jobConfiguration->setParameters(json_encode($jobStartRequest->getJobParameters()));
        }

        $jobTaskConfiguration = $jobStartRequest->getTaskConfigurationCollection()->get();

        foreach ($jobTaskConfiguration as $taskConfiguration) {
            $jobConfiguration->addTaskConfiguration($taskConfiguration);
        }

        return $jobConfiguration;
    }
}
