<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration\RequestAdapter;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as JobConfigurationValues;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class JobConfigurationController extends Controller
{
    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
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
            return Response::create('', 400, [
                'X-JobConfigurationCreate-Error' => json_encode([
                    'code' => 99,
                    'message' => 'Special users cannot create job configurations'
                ])
            ]);
        }

        $website = $websiteService->get($requestWebsite);

        $jobType = $jobTypeService->get($requestType);

        if (empty($jobType)) {
            $jobType = $jobTypeService->getFullSiteType();
        }

        $adapter = new RequestAdapter();
        $adapter->setRequest($request);
        $adapter->setTaskTypeService($taskTypeService);

        $taskConfigurationCollection = $adapter->getCollection();

        $jobConfigurationValues = new JobConfigurationValues();
        $jobConfigurationValues->setWebsite($website);
        $jobConfigurationValues->setType($jobType);
        $jobConfigurationValues->setTaskConfigurationCollection($taskConfigurationCollection);
        $jobConfigurationValues->setLabel($requestLabel);
        $jobConfigurationValues->setParameters($requestData->get('parameters'));

        try {
            $jobConfiguration = $jobConfigurationService->create($jobConfigurationValues);

            return $this->redirect($this->generateUrl(
                'jobconfiguration_get_get',
                ['label' => $jobConfiguration->getLabel()]
            ));
        } catch (JobConfigurationServiceException $jobConfigurationServiceException) {
            return Response::create('', 400, [
                'X-JobConfigurationCreate-Error' => json_encode([
                    'code' => $jobConfigurationServiceException->getCode(),
                    'message' => $jobConfigurationServiceException->getMessage()
                ])
            ]);
        }
    }

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

    /**
     * @param string $label
     *
     * @return JsonResponse
     */
    public function getAction($label)
    {
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');

        $label = trim($label);

        $jobConfiguration = $jobConfigurationService->get($label);
        if (empty($jobConfiguration)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($jobConfiguration);
    }

    /**
     * @return JsonResponse
     */
    public function listAction()
    {
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');

        return new JsonResponse($jobConfigurationService->getList());
    }

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
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $jobConfiguration = $jobConfigurationService->get($label);
        if (empty($jobConfiguration)) {
            throw new NotFoundHttpException();
        }

        $newJobConfigurationValues = new JobConfigurationValues();

        $requestData = $request->request;

        $requestWebsite = trim($requestData->get('website'));
        $website = $websiteService->get($requestWebsite);

        $requestJobType = trim($requestData->get('type'));
        $jobType = $jobTypeService->get($requestJobType);

        if (empty($jobType)) {
            $jobType = $jobTypeService->getFullSiteType();
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
                'jobconfiguration_get',
                ['label' => $jobConfiguration->getLabel()]
            ));
        } catch (JobConfigurationServiceException $jobConfigurationServiceException) {
            return Response::create('', 400, [
                'X-JobConfigurationUpdate-Error' => json_encode([
                    'code' => $jobConfigurationServiceException->getCode(),
                    'message' => $jobConfigurationServiceException->getMessage()
                ])
            ]);
        }
    }
}
