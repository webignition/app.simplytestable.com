<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;

class GetListController extends ApiController
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
