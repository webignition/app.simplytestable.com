<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetController extends ApiController
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
            throw new NotFoundHttpException();
        }

        return $this->sendResponse($jobConfiguration);
    }
}
