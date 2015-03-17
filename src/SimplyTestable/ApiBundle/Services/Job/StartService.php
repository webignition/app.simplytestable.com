<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;

class StartService {

    public function start(JobConfiguration $jobConfiguration) {
        if (!$jobConfiguration->getWebsite()->isPubliclyRoutable()) {
            throw new JobStartServiceException(
                'Unroutable website',
                JobStartServiceException::CODE_UNROUTABLE_WEBSITE
            );
        }
    }



}