<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use Symfony\Component\HttpFoundation\Response;

class GetController extends JobConfigurationController
{
    /**
     * @param string $label
     *
     * @return Response
     */
    public function getAction($label)
    {
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');

        $label = trim($label);

        $jobConfigurationService->setUser($this->getUser());

        $jobConfiguration = $jobConfigurationService->get($label);
        if (empty($jobConfiguration)) {
            return $this->sendNotFoundResponse();
        }

        return $this->sendResponse($jobConfiguration);
    }
}
