<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration\RequestAdapter;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UpdateController extends JobConfigurationController
{
    /**
     * @param Request $request
     * @param string $label
     *
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request, $label)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $jobConfigurationService->setUser($this->getUser());

        $jobConfiguration = $jobConfigurationService->get($label);
        if (empty($jobConfiguration)) {
            throw new NotFoundHttpException();
        }

        $newJobConfigurationValues = new JobConfigurationValues();

        $requestData = $request->request;

        $website = $websiteService->fetch(trim($requestData->get('website')));
        $jobType = $jobTypeService->getByName(trim($requestData->get('type')));

        if (empty($jobType)) {
            $jobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);
        }

        $adapter = new RequestAdapter();
        $adapter->setRequest($request);
        $adapter->setTaskTypeService($taskTypeService);

        $taskConfigurationCollection = $adapter->getCollection();

        $newJobConfigurationValues->setLabel($requestData->get('label'));
        $newJobConfigurationValues->setParameters($requestData->get('parameters'));
        $newJobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        $newJobConfigurationValues->setWebsite($website);
        $newJobConfigurationValues->setType($jobType);

        try {
            $jobConfigurationService->update(
                $jobConfiguration,
                $newJobConfigurationValues
            );

            return $this->redirect($this->generateUrl(
                'jobconfiguration_get_get',
                ['label' => $jobConfiguration->getLabel()]
            ));
        } catch (JobConfigurationServiceException $jobConfigurationServiceException) {
            return $this->sendFailureResponse([
                'X-JobConfigurationUpdate-Error' => json_encode([
                    'code' => $jobConfigurationServiceException->getCode(),
                    'message' => $jobConfigurationServiceException->getMessage()
                ])
            ]);
        }
    }
}
