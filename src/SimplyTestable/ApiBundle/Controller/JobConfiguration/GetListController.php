<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class GetListController extends Controller
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
