<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\Resque\JobFactoryService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use webignition\NormalisedUrl\NormalisedUrl;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationServiceException;

class JobPreparationService
{
    const RETURN_CODE_OK = 0;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var JobTypeService
     */
    private $jobTypeService;

    /**
     * @var array
     */
    private $processedUrls = array();

    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * @var array
     */
    private $predefinedDomainsToIgnore = array();

    /**
     * @var CrawlJobContainerService
     */
    private $crawlJobContainerService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var QueueService
     */
    private $resqueService;

    /**
     * @var JobFactoryService
     */
    private $resqueJobFactoryService;

    /**
     * @var UrlFinder
     */
    private $urlFinder;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @param JobService $jobService
     * @param TaskService $taskService
     * @param JobTypeService $jobTypeService
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param UserService $userService
     * @param QueueService $resqueQueueService
     * @param JobFactoryService $resqueJobFactoryService
     * @param UrlFinder $urlFinder
     * @param StateService $stateService
     */
    public function __construct(
        JobService $jobService,
        TaskService $taskService,
        JobTypeService $jobTypeService,
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        CrawlJobContainerService $crawlJobContainerService,
        UserService $userService,
        QueueService $resqueQueueService,
        JobFactoryService $resqueJobFactoryService,
        UrlFinder $urlFinder,
        StateService $stateService
    ) {
        $this->jobService = $jobService;
        $this->taskService = $taskService;
        $this->jobTypeService = $jobTypeService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->userService = $userService;
        $this->resqueService = $resqueQueueService;
        $this->resqueJobFactoryService = $resqueJobFactoryService;
        $this->urlFinder = $urlFinder;
        $this->stateService = $stateService;
    }

    /**
     * @param TaskType $taskType
     * @param string[] $domainsToIgnore
     */
    public function setPredefinedDomainsToIgnore(TaskType $taskType, $domainsToIgnore)
    {
        $this->predefinedDomainsToIgnore[$taskType->getName()] = $domainsToIgnore;
    }

    /**
     * @param Job $job
     * @throws JobPreparationServiceException
     */
    public function prepare(Job $job)
    {
        if (!$this->jobService->isResolved($job)) {
            throw new JobPreparationServiceException(
                'Job is in wrong state, currently "'.$job->getState()->getName().'"',
                JobPreparationServiceException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }

        $jobPreparingState = $this->stateService->fetch(JobService::PREPARING_STATE);

        $job->setState($jobPreparingState);
        $this->jobService->persistAndFlush($job);

        $this->processedUrls = array();

        $this->jobUserAccountPlanEnforcementService->setUser($job->getUser());
        $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint()->getLimit();

        $urls = $this->collectUrlsForJob(
            $job,
            $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint()->getLimit()
        );

        if (empty($urls)) {
            $jobFailedNoSitemapState = $this->stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE);

            $job->setState($jobFailedNoSitemapState);

            if (!$this->userService->isPublicUser($job->getUser())) {
                if (!$this->crawlJobContainerService->hasForJob($job)) {
                    $crawlJobContainer = $this->crawlJobContainerService->getForJob($job);
                    $this->crawlJobContainerService->prepare($crawlJobContainer);
                }
            }

            $this->jobService->persistAndFlush($job);
        } else {
            if ($this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached(count($urls))) {
                $constraint = $this->jobUserAccountPlanEnforcementService->getJobUrlLimitConstraint();

                $this->jobService->addAmmendment(
                    $job,
                    'plan-url-limit-reached:discovered-url-count-' . count($urls),
                    $constraint
                );
                $urls = array_slice($urls, 0, $constraint->getLimit());
            }

            $this->prepareTasksFromCollectedUrls($job, $urls);

            $jobQueuedState = $this->stateService->fetch(JobService::QUEUED_STATE);

            $job->setState($jobQueuedState);

            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime());
            $job->setTimePeriod($timePeriod);

            $this->jobService->persistAndFlush($job);
        }
    }

    /**
     * @param CrawlJobContainer $crawlJobContainer
     * @throws JobPreparationServiceException
     *
     * @return int
     */
    public function prepareFromCrawl(CrawlJobContainer $crawlJobContainer)
    {
        $this->processedUrls = array();
        $job = $crawlJobContainer->getParentJob();

        if (!$this->jobService->isFailedNoSitemap($job)) {
            throw new JobPreparationServiceException(
                'Job is in wrong state, currently "'.$job->getState()->getName().'"',
                JobPreparationServiceException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }

        $jobPreparingState = $this->stateService->fetch(JobService::PREPARING_STATE);

        $job->setState($jobPreparingState);
        $this->jobService->persistAndFlush($job);

        $urls = $this->crawlJobContainerService->getDiscoveredUrls($crawlJobContainer, true);

        if ($crawlJobContainer->getCrawlJob()->getAmmendments()->count()) {
            /* @var $ammendment \SimplyTestable\ApiBundle\Entity\Job\Ammendment */

            foreach ($crawlJobContainer->getCrawlJob()->getAmmendments() as $ammendment) {
                /* @var Ammendment $ammendment */
                $constraint = $ammendment->getConstraint();

                if ($constraint->getName() == JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME) {
                    $this->jobService->addAmmendment($job, $ammendment->getReason(), $constraint);
                }
            }
        }

        $this->prepareTasksFromCollectedUrls($job, $urls);

        $jobQueuedState = $this->stateService->fetch(JobService::QUEUED_STATE);

        $job->setState($jobQueuedState);

        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $job->setTimePeriod($timePeriod);

        $this->jobService->persistAndFlush($job);

        return self::RETURN_CODE_OK;
    }

    /**
     * @param Job $job
     * @param string[] $urls
     */
    private function prepareTasksFromCollectedUrls(Job $job, $urls)
    {
        $requestedTaskTypes = $job->getRequestedTaskTypes();

        $newTaskState = $this->taskService->getQueuedState();

        foreach ($urls as $url) {
            $comparatorUrl = new NormalisedUrl($url);
            if (!$this->isProcessedUrl($comparatorUrl)) {
                foreach ($requestedTaskTypes as $taskType) {
                    $taskTypeOptions = $this->getTaskTypeOptions($job, $taskType);

                    $task = new Task();
                    $task->setJob($job);
                    $task->setType($taskType);
                    $task->setUrl($url);
                    $task->setState($newTaskState);

                    $job->addTask($task);

                    $parameters = array();

                    if ($taskTypeOptions->getOptionCount()) {
                        $parameters = $taskTypeOptions->getOptions();

                        $domainsToIgnore = $this->getDomainsToIgnore(
                            $taskTypeOptions,
                            $this->predefinedDomainsToIgnore
                        );

                        if (count($domainsToIgnore)) {
                            $parameters['domains-to-ignore'] = $domainsToIgnore;
                        }
                    }

                    if ($job->hasParameters()) {
                        $parameters = array_merge($parameters, json_decode($job->getParameters(), true));
                    }

                    $task->setParameters(json_encode($parameters));

                    $this->taskService->persist($task);
                }

                $this->processedUrls[] = (string)$comparatorUrl;
            }
        }
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    private function isSingleUrlJob(Job $job)
    {
        return $job->getType()->equals($this->jobTypeService->getSingleUrlType());
    }

    /**
     * @param Job $job
     * @param int $softLimit
     *
     * @return string[]
     */
    private function collectUrlsForJob(Job $job, $softLimit)
    {
        $parameters = ($job->hasParameters()) ? json_decode($job->getParameters(), true) : array();

        if ($this->isSingleUrlJob($job)) {
            return array($job->getWebsite()->getCanonicalUrl());
        }

        return $this->urlFinder->getUrls($job->getWebsite(), $softLimit, $parameters);
    }

    /**
     * @param NormalisedUrl $url
     *
     * @return bool
     */
    private function isProcessedUrl(NormalisedUrl $url)
    {
        return in_array((string)$url, $this->processedUrls);
    }

    /**
     * @param Job $job
     * @param TaskType $taskType
     *
     * @return TaskTypeOptions
     */
    private function getTaskTypeOptions(Job $job, TaskType $taskType)
    {
        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            /* @var $taskTypeOptions TaskTypeOptions */
            if ($taskTypeOptions->getTaskType()->equals($taskType)) {
                return $taskTypeOptions;
            }
        }

        return new TaskTypeOptions();
    }

    /**
     * @param TaskTypeOptions $taskTypeOptions
     * @param string[] $predefinedDomainsToIgnore
     *
     * @return string[]
     */
    private function getDomainsToIgnore(TaskTypeOptions $taskTypeOptions, $predefinedDomainsToIgnore)
    {
        $rawDomainsToIgnore = array();

        if ($this->shouldIgnoreCommonCdns($taskTypeOptions)) {
            if (isset($predefinedDomainsToIgnore[$taskTypeOptions->getTaskType()->getName()])) {
                $rawDomainsToIgnore = array_merge(
                    $rawDomainsToIgnore,
                    $predefinedDomainsToIgnore[$taskTypeOptions->getTaskType()->getName()]
                );
            }
        }

        if ($this->hasDomainsToIgnore($taskTypeOptions)) {
            $specifiedDomainsToIgnore = $taskTypeOptions->getOption('domains-to-ignore');
            if (is_array($specifiedDomainsToIgnore)) {
                $rawDomainsToIgnore = array_merge($rawDomainsToIgnore, $specifiedDomainsToIgnore);
            }
        }

        $domainsToIgnore = array();
        foreach ($rawDomainsToIgnore as $domainToIgnore) {
            $domainToIgnore = trim(strtolower($domainToIgnore));
            if (!in_array($domainToIgnore, $domainsToIgnore)) {
                $domainsToIgnore[] = $domainToIgnore;
            }
        }

        return $domainsToIgnore;
    }

    /**
     * @param TaskTypeOptions $taskTypeOptions
     *
     * @return bool
     */
    private function shouldIgnoreCommonCdns(TaskTypeOptions $taskTypeOptions)
    {
        return $taskTypeOptions->getOption('ignore-common-cdns') == '1';
    }

    /**
     * @param TaskTypeOptions $taskTypeOptions
     *
     * @return bool
     */
    private function hasDomainsToIgnore(TaskTypeOptions $taskTypeOptions)
    {
        return is_array($taskTypeOptions->getOption('domains-to-ignore'));
    }
}
