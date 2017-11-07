<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class DeleteController extends Controller
{
    /**
     * @param $label
     *
     * @return Response
     */
    public function deleteAction($label)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $scheduledJobRepository = $this->container->get('simplytestable.repository.scheduledjob');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $label = trim($label);

        $jobConfiguration = $jobConfigurationService->get($label);
        if (is_null($jobConfiguration)) {
            throw new NotFoundHttpException();
        }

        $scheduledJob = $scheduledJobRepository->findOneBy([
            'jobConfiguration' => $jobConfiguration,
        ]);

        if (empty($scheduledJob)) {
            $jobConfigurationService->delete($label);

            return new Response();
        }

        return Response::create('', 400, [
            'X-JobConfigurationDelete-Error' => json_encode([
                'code' => 1,
                'message' => 'Job configuration is in use by a scheduled job'
            ])
        ]);
    }
}
