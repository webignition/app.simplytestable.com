<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use Symfony\Component\HttpFoundation\Response;

class DeleteController extends JobConfigurationController
{
    /**
     * @param $label
     *
     * @return Response
     */
    public function deleteAction($label)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $label = trim($label);

        $jobConfigurationService->setUser($this->getUser());

        $jobConfiguration = $jobConfigurationService->get($label);
        if (is_null($jobConfiguration)) {
            return $this->sendNotFoundResponse();
        }

        $scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);
        $scheduledJob = $scheduledJobRepository->findOneBy([
            'jobConfiguration' => $jobConfiguration,
        ]);

        if (empty($scheduledJob)) {
            $jobConfigurationService->delete($label);

            return $this->sendResponse();
        }

        return $this->sendFailureResponse([
            'X-JobConfigurationDelete-Error' => json_encode([
                'code' => 1,
                'message' => 'Job configuration is in use by a scheduled job'
            ])
        ]);
    }
}
