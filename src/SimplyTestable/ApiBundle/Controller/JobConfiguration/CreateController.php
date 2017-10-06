<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration\RequestAdapter;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateController extends JobConfigurationController
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $requestData = $request->request;

        $requestLabel = rawurldecode(trim($requestData->get('label')));

        if (empty($requestLabel)) {
            throw new BadRequestHttpException('"label" missing');
        }

        $requestWebsite = rawurldecode(trim($requestData->get('website')));

        if (empty($requestWebsite)) {
            throw new BadRequestHttpException('"website" missing');
        }

        $requestType = rawurldecode(trim($requestData->get('type')));

        if (empty($requestType)) {
            throw new BadRequestHttpException('"type" missing');
        }

        $requestTaskConfiguration = $requestData->get('task-configuration');

        if (empty($requestTaskConfiguration)) {
            throw new BadRequestHttpException('"task-configuration" missing');
        }

        $user = $this->getUser();

        if ($userService->isSpecialUser($user)) {
            return $this->sendFailureResponse([
                'X-JobConfigurationCreate-Error' => json_encode([
                    'code' => 99,
                    'message' => 'Special users cannot create job configurations'
                ])
            ]);
        }

        $this->request = $request;

        $jobConfigurationService->setUser($user);

        $website = $websiteService->fetch($requestWebsite);
        $jobType = $this->getJobType($entityManager, $requestType);
        $taskConfigurationCollection = $this->getRequestTaskConfigurationCollection($request, $taskTypeService);

        $jobConfigurationValues = new JobConfigurationValues();
        $jobConfigurationValues->setWebsite($website);
        $jobConfigurationValues->setType($jobType);
        $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        $jobConfigurationValues->setLabel($requestLabel);
        $jobConfigurationValues->setParameters($requestData->get('parameters'));

        try {
            $jobConfiguration = $this->getJobConfigurationService()->create($jobConfigurationValues);

            return $this->redirect($this->generateUrl(
                'jobconfiguration_get_get',
                ['label' => $jobConfiguration->getLabel()]
            ));
        } catch (JobConfigurationServiceException $jobConfigurationServiceException) {
            return $this->sendFailureResponse([
                'X-JobConfigurationCreate-Error' => json_encode([
                    'code' => $jobConfigurationServiceException->getCode(),
                    'message' => $jobConfigurationServiceException->getMessage()
                ])
            ]);
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param string $requestType
     *
     * @return JobType
     */
    private function getJobType(EntityManager $entityManager, $requestType)
    {
        $jobTypeRepository = $entityManager->getRepository(JobType::class);

        $jobType = $jobTypeRepository->findOneBy([
            'name' => $requestType,
        ]);
        if (empty($jobType)) {
            $jobType = $jobTypeRepository->findOneBy([
                'name' => JobTypeService::FULL_SITE_NAME,
            ]);
        }

        return $jobType;
    }

    /**
     * @param Request $request
     * @param TaskTypeService $taskTypeService
     *
     * @return TaskConfigurationCollection
     */
    private function getRequestTaskConfigurationCollection(Request $request, TaskTypeService $taskTypeService)
    {
        $adapter = new RequestAdapter();
        $adapter->setRequest($request);
        $adapter->setTaskTypeService($taskTypeService);

        return $adapter->getCollection();
    }
}
