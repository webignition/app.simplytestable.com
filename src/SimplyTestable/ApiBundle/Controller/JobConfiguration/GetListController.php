<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;

class GetListController extends ApiController
{
    /**
     * @return JsonResponse
     */
    public function listAction()
    {
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $jobConfigurationService->setUser($this->getUser());

        return new JsonResponse($jobConfigurationService->getList());
    }
}
