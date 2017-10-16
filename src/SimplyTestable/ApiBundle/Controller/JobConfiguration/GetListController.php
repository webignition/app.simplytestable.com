<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use Symfony\Component\HttpFoundation\Response;

class GetListController extends JobConfigurationController
{
    /**
     * @return Response
     */
    public function listAction()
    {
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $jobConfigurationService->setUser($this->getUser());

        return $this->sendResponse($jobConfigurationService->getList());
    }
}
