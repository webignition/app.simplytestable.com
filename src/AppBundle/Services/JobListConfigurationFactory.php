<?php
namespace AppBundle\Services;

use AppBundle\Model\JobList\Configuration;
use AppBundle\Request\Job\ListRequest;

class JobListConfigurationFactory
{
    public function createFromJobListRequest(ListRequest $jobListRequest)
    {
        $configuration = new Configuration([
            Configuration::KEY_USER => $jobListRequest->getUser(),
            Configuration::KEY_TYPES_TO_EXCLUDE => $jobListRequest->getTypesToExclude(),
            Configuration::KEY_STATES_TO_EXCLUDE => $jobListRequest->getStatesToExclude(),
            Configuration::KEY_URL_FILTER => $jobListRequest->getUrlFilter(),
            Configuration::KEY_JOB_IDS_TO_EXCLUDE => $jobListRequest->getJobIdsToExclude(),
            Configuration::KEY_JOB_IDS_TO_INCLUDE => $jobListRequest->getJobIdsToInclude()
        ]);

        return $configuration;
    }
}
