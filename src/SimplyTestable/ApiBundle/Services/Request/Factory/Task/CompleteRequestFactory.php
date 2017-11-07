<?php

namespace SimplyTestable\ApiBundle\Services\Request\Factory\Task;

use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Request\Task\CompleteRequest;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;

class CompleteRequestFactory
{
    const PARAMETER_END_DATE_TIME = 'end_date_time';
    const PARAMETER_OUTPUT = 'output';
    const PARAMETER_CONTENT_TYPE = 'contentType';
    const PARAMETER_STATE = 'state';
    const PARAMETER_ERROR_COUNT = 'errorCount';
    const PARAMETER_WARNING_COUNT = 'warningCount';
    const ATTRIBUTE_ROUTE_PARAMS = '_route_params';
    const ROUTE_PARAM_CANONICAL_URL = 'canonical_url';
    const ROUTE_PARAM_TASK_TYPE = 'task_type';
    const ROUTE_PARAM_PARAMETER_HASH = 'parameter_hash';

    /**
     * @var string[]
     */
    private $allowedStateNames = [
        TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
        TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
        TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
        TaskService::TASK_SKIPPED_STATE,
    ];

    /**
     * @var ParameterBag
     */
    private $requestParameters;

    /**
     * @var array
     */
    private $routeParams;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @param RequestStack $requestStack
     * @param StateService $stateService
     * @param TaskTypeService $taskTypeService
     * @param TaskService $taskService
     */
    public function __construct(
        RequestStack $requestStack,
        StateService $stateService,
        TaskTypeService $taskTypeService,
        TaskService $taskService
    ) {
        $request = $requestStack->getCurrentRequest();
        $this->init($request);

        $this->stateService = $stateService;
        $this->taskTypeService = $taskTypeService;
        $this->taskService = $taskService;
    }

    /**
     * @param Request $request
     */
    public function init(Request $request)
    {
        $this->requestParameters = $request->request;
        $this->routeParams = $request->attributes->get(self::ATTRIBUTE_ROUTE_PARAMS);
    }

    /**
     * @return CompleteRequest
     */
    public function create()
    {
        return new CompleteRequest(
            $this->getEndDateTimeFromParameters(),
            $this->requestParameters->get(self::PARAMETER_OUTPUT),
            $this->getContentTypeFromParameters(),
            $this->getStateFromParameters(),
            (int)$this->requestParameters->get(self::PARAMETER_ERROR_COUNT),
            (int)$this->requestParameters->get(self::PARAMETER_WARNING_COUNT),
            $this->getTasks()
        );
    }

    /**
     * @return \DateTime|null
     */
    private function getEndDateTimeFromParameters()
    {
        $endDateTimeValue = $this->requestParameters->get(self::PARAMETER_END_DATE_TIME);
        return (empty($endDateTimeValue))
            ? null
            : new \DateTime($endDateTimeValue);
    }

    /**
     * @return InternetMediaType|null
     */
    private function getContentTypeFromParameters()
    {
        $contentTypeValue = $this->requestParameters->get(self::PARAMETER_CONTENT_TYPE);
        if (empty($contentTypeValue)) {
            return null;
        }

        $internetMediaTypeParser = new InternetMediaTypeParser();

        return $internetMediaTypeParser->parse($contentTypeValue);
    }

    /**
     * @return State
     */
    private function getStateFromParameters()
    {
        $stateValue = $this->requestParameters->get(self::PARAMETER_STATE);
        if (empty($stateValue) || !in_array($stateValue, $this->allowedStateNames)) {
            $stateValue = TaskService::COMPLETED_STATE;
        }

        return $this->stateService->get($stateValue);
    }

    /**
     * @return Task[]|null
     */
    private function getTasks()
    {
        $taskType = $this->taskTypeService->getByName(
            urldecode($this->routeParams[self::ROUTE_PARAM_TASK_TYPE])
        );

        if (empty($taskType)) {
            return null;
        }

        $tasks = $this->taskService->getEquivalentTasks(
            trim($this->routeParams[self::ROUTE_PARAM_CANONICAL_URL]),
            $taskType,
            trim($this->routeParams[self::ROUTE_PARAM_PARAMETER_HASH]),
            $this->stateService->getCollection($this->taskService->getIncompleteStateNames())
        );

        return (empty($tasks))
            ? null
            : $tasks;
    }
}
