<?php

namespace App\Controller;

use App\Adapter\Job\TaskConfiguration\RequestAdapter;
use App\Entity\User;
use App\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use App\Model\Job\Configuration\Values as JobConfigurationValues;
use App\Services\ApplicationStateService;
use App\Services\Job\ConfigurationService;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class JobConfigurationController
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var ConfigurationService
     */
    private $jobConfigurationService;

    /**
     * @param RouterInterface $router
     * @param ApplicationStateService $applicationStateService
     * @param ConfigurationService $jobConfigurationService
     */
    public function __construct(
        RouterInterface $router,
        ApplicationStateService $applicationStateService,
        ConfigurationService $jobConfigurationService
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->jobConfigurationService = $jobConfigurationService;
        $this->router = $router;
    }

    /**
     * @param UserService $userService
     * @param WebSiteService $websiteService
     * @param TaskTypeService $taskTypeService
     * @param JobTypeService $jobTypeService
     * @param UserInterface|User $user
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(
        UserService $userService,
        WebSiteService $websiteService,
        TaskTypeService $taskTypeService,
        JobTypeService $jobTypeService,
        UserInterface $user,
        Request $request
    ) {
        if ($this->applicationStateService->isInReadOnlyMode()) {
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

        $parameters = $requestData->get('parameters');
        $parameters = empty($parameters) ? '[]' : $parameters;

        $adapter = new RequestAdapter();
        $adapter->setRequest($request);
        $adapter->setTaskTypeService($taskTypeService);

        $taskConfigurationCollection = $adapter->getCollection();

        $jobConfigurationValues = new JobConfigurationValues(
            $requestLabel,
            $website,
            $jobType,
            $taskConfigurationCollection,
            $parameters
        );

        try {
            $jobConfiguration = $this->jobConfigurationService->create($jobConfigurationValues);

            return $this->redirect(
                'jobconfiguration_get',
                ['id' => $jobConfiguration->getId()]
            );
        } catch (JobConfigurationServiceException $jobConfigurationServiceException) {
            return Response::create('', 400, [
                'X-JobConfigurationCreate-Error' => json_encode([
                    'code' => $jobConfigurationServiceException->getCode(),
                    'message' => $jobConfigurationServiceException->getMessage()
                ])
            ]);
        }
    }

    public function deleteAction(int $id):Response
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $jobConfiguration = $this->jobConfigurationService->getById($id);
        if (is_null($jobConfiguration)) {
            throw new NotFoundHttpException();
        }

        $this->jobConfigurationService->delete($id);

        return new Response();
    }

    public function getAction(int $id): JsonResponse
    {
        $jobConfiguration = $this->jobConfigurationService->getById($id);
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
        return new JsonResponse($this->jobConfigurationService->getList());
    }

    /**
     * @param WebSiteService $websiteService
     * @param TaskTypeService $taskTypeService
     * @param JobTypeService $jobTypeService
     * @param Request $request
     * @param int $id
     *
     * @return RedirectResponse|Response
     */
    public function updateAction(
        WebSiteService $websiteService,
        TaskTypeService $taskTypeService,
        JobTypeService $jobTypeService,
        Request $request,
        int $id
    ) {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $jobConfiguration = $this->jobConfigurationService->getById($id);
        if (empty($jobConfiguration)) {
            throw new NotFoundHttpException();
        }

        $requestData = $request->request;

        $requestLabel = trim($requestData->get('label'));
        $label = empty($requestLabel) ? $jobConfiguration->getLabel() : $requestLabel;

        $requestWebsite = trim($requestData->get('website'));
        $website = empty($requestWebsite) ? $jobConfiguration->getWebsite() : $websiteService->get($requestWebsite);

        $requestJobType = trim($requestData->get('type'));
        $jobType = empty($requestJobType) ? $jobConfiguration->getType() : $jobTypeService->get($requestJobType);
        if (empty($jobType)) {
            $jobType = $jobTypeService->getFullSiteType();
        }

        $requestParameters = trim($requestData->get('parameters'));
        $parameters = empty($requestParameters) ? $jobConfiguration->getParameters() : $requestParameters;

        $adapter = new RequestAdapter();
        $adapter->setRequest($request);
        $adapter->setTaskTypeService($taskTypeService);

        $taskConfigurationCollection = $adapter->getCollection();

        if ($taskConfigurationCollection->isEmpty()) {
            $taskConfigurationCollection = $jobConfiguration->getTaskConfigurationCollection();
        }

        $newJobConfigurationValues = new JobConfigurationValues(
            $label,
            $website,
            $jobType,
            $taskConfigurationCollection,
            $parameters
        );

        try {
            $this->jobConfigurationService->update(
                $jobConfiguration,
                $newJobConfigurationValues
            );

            return $this->redirect(
                'jobconfiguration_get',
                ['id' => $jobConfiguration->getId()]
            );
        } catch (JobConfigurationServiceException $jobConfigurationServiceException) {
            return Response::create('', 400, [
                'X-JobConfigurationUpdate-Error' => json_encode([
                    'code' => $jobConfigurationServiceException->getCode(),
                    'message' => $jobConfigurationServiceException->getMessage()
                ])
            ]);
        }
    }

    /**
     * @param string  $routeName
     * @param array $routeParameters
     *
     * @return RedirectResponse
     */
    private function redirect($routeName, $routeParameters = [])
    {
        $url = $this->router->generate(
            $routeName,
            $routeParameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new RedirectResponse($url);
    }
}
