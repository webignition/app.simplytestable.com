<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Adapter\Job\Configuration\Start\RequestAdapter;
use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Services\Job\StartService as JobStartService;
use Symfony\Component\HttpFoundation\Request;

class JobFactory
{
    const DEFAULT_TYPE = JobTypeService::FULL_SITE_NAME;
    const DEFAULT_SITE_ROOT_URL = 'http://example.com';

    const KEY_TYPE = 'type';
    const KEY_SITE_ROOT_URL = 'siteRootUrl';
    const KEY_TEST_TYPES = 'testTypes';
    const KEY_TEST_TYPE_OPTIONS = 'testTypeOptions';
    const KEY_PARAMETERS = 'parameters';
    const KEY_USER = 'user';

    /**
     * @var array
     */
    private $defaultJobValues = [
        self::KEY_TYPE => self::DEFAULT_TYPE,
        self::KEY_SITE_ROOT_URL => self::DEFAULT_SITE_ROOT_URL,
        self::KEY_TEST_TYPES => ['html validation'],
        self::KEY_TEST_TYPE_OPTIONS => [],
        self::KEY_PARAMETERS => [],
        self::KEY_USER => null,
    ];

    /**
     * @var JobTypeService
     */
    private $jobTypeService;

    /**
     * @var WebSiteService
     */
    private $websiteService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var JobStartService
     */
    private $jobStartService;

    /**
     * @var WebsiteResolutionService
     */
    private $websiteResolutionService;

    /**
     * @var JobPreparationService
     */
    private $jobPreparationService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @param JobTypeService $jobTypeService
     * @param WebSiteService $websiteService
     * @param TaskTypeService $taskTypeService
     * @param JobStartService $jobStartService
     * @param WebsiteResolutionService $websiteResolutionService
     * @param JobPreparationService $jobPreparationService
     * @param TaskService $taskService
     * @param UserService $userService
     */
    public function __construct(
        JobTypeService $jobTypeService,
        WebsiteService $websiteService,
        TaskTypeService $taskTypeService,
        JobStartService $jobStartService,
        WebsiteResolutionService $websiteResolutionService,
        JobPreparationService $jobPreparationService,
        TaskService $taskService,
        UserService $userService
    ) {
        $this->jobTypeService = $jobTypeService;
        $this->websiteService = $websiteService;
        $this->taskTypeService = $taskTypeService;
        $this->jobStartService = $jobStartService;
        $this->websiteResolutionService = $websiteResolutionService;
        $this->jobPreparationService = $jobPreparationService;
        $this->taskService = $taskService;

        $this->defaultJobValues[self::KEY_USER] = $userService->getPublicUser();
    }

    /**
     * @param array $jobValues
     *
     * @return Job
     */
    public function createResolveAndPrepare($jobValues = [])
    {
        $job = $this->create($jobValues);
        $this->resolve($job);
        $this->prepare($job);

        return $job;
    }

    /**
     * @param array $jobValues
     * @return Job
     */
    public function create($jobValues = [])
    {
        foreach ($this->defaultJobValues as $key => $value) {
            if (!isset($jobValues[$key])) {
                $jobValues[$key] = $value;
            }
        }

        $request = new Request([], [
            'test-types' => $jobValues[self::KEY_TEST_TYPES],
            'test-type-options' => $jobValues[self::KEY_TEST_TYPE_OPTIONS],
            'parameters' => $jobValues[self::KEY_PARAMETERS],
        ], [
            'site_root_url' => $jobValues[self::KEY_SITE_ROOT_URL],
            'type' => $jobValues[self::KEY_TYPE],
        ]);

        $requestAdapter = new RequestAdapter(
            $request,
            $this->websiteService,
            $this->jobTypeService,
            $this->taskTypeService
        );

        $jobConfiguration = $requestAdapter->getJobConfiguration();
        $jobConfiguration->setUser($jobValues[self::KEY_USER]);

        return $this->jobStartService->start($jobConfiguration);
    }

    /**
     * @param Job $job
     */
    public function resolve(Job $job)
    {
        $this->websiteResolutionService->resolve($job);
    }

    /**
     * @param Job $job
     */
    public function prepare(Job $job)
    {
        $this->jobPreparationService->prepare($job);
    }

    /**
     * @param Job $job
     * @param State $state
     */
    public function setTaskStates(Job $job, State $state)
    {
        foreach ($job->getTasks() as $task) {
            $task->setState($state);
            $this->taskService->persistAndFlush($task);
        }
    }
}
