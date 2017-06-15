<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Adapter\Job\Configuration\Start\RequestAdapter;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Services\Job\StartService as JobStartService;
use Symfony\Component\HttpFoundation\Request;

class JobFactory
{
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
     */
    public function __construct(
        JobTypeService $jobTypeService,
        WebsiteService $websiteService,
        TaskTypeService $taskTypeService,
        JobStartService $jobStartService,
        WebsiteResolutionService $websiteResolutionService,
        JobPreparationService $jobPreparationService,
        TaskService $taskService
    ) {
        $this->jobTypeService = $jobTypeService;
        $this->websiteService = $websiteService;
        $this->taskTypeService = $taskTypeService;
        $this->jobStartService = $jobStartService;
        $this->websiteResolutionService = $websiteResolutionService;
        $this->jobPreparationService = $jobPreparationService;
        $this->taskService = $taskService;
    }

    /**
     * @param string $type
     * @param string $siteRootUrl
     * @param string[] $testTypes
     * @param array $testTypeOptions
     * @param array $parameters
     * @param User $user
     * @return Job
     */
    public function createResolveAndPrepare($type, $siteRootUrl, $testTypes, $testTypeOptions, $parameters, User $user)
    {
        $job = $this->create($type, $siteRootUrl, $testTypes, $testTypeOptions, $parameters, $user);
        $this->resolve($job);
        $this->prepare($job);

        return $job;
    }

    /**
     * @param string $type
     * @param string $siteRootUrl
     * @param string[] $testTypes
     * @param array $testTypeOptions
     * @param array $parameters
     * @param User $user
     * @return Job
     */
    public function create($type, $siteRootUrl, $testTypes, $testTypeOptions, $parameters, User $user)
    {
        $request = new Request([], [
            'test-types' => $testTypes,
            'test-type-options' => $testTypeOptions,
            'parameters' => $parameters,
        ], [
            'site_root_url' => $siteRootUrl,
            'type' => $type,
        ]);

        $requestAdapter = new RequestAdapter(
            $request,
            $this->websiteService,
            $this->jobTypeService,
            $this->taskTypeService
        );

        $jobConfiguration = $requestAdapter->getJobConfiguration();
        $jobConfiguration->setUser($user);

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
