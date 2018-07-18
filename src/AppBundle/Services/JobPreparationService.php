<?php
namespace AppBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Job\Ammendment;
use AppBundle\Entity\Job\Job;
use AppBundle\Entity\Task\Task;
use AppBundle\Entity\Task\Type\Type as TaskType;
use AppBundle\Entity\Job\TaskTypeOptions;
use AppBundle\Entity\TimePeriod;
use AppBundle\Model\Parameters;
use webignition\NormalisedUrl\NormalisedUrl;
use AppBundle\Entity\CrawlJobContainer;
use AppBundle\Exception\Services\JobPreparation\Exception as JobPreparationServiceException;

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
     * @var UrlFinder
     */
    private $urlFinder;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CrawlJobUrlCollector
     */
    private $crawlJobUrlCollector;

    /**
     * @param JobService $jobService
     * @param TaskService $taskService
     * @param JobTypeService $jobTypeService
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param UserService $userService
     * @param UrlFinder $urlFinder
     * @param StateService $stateService
     * @param UserAccountPlanService $userAccountPlanService
     * @param EntityManagerInterface $entityManager
     * @param CrawlJobUrlCollector $crawlJobUrlCollector
     */
    public function __construct(
        JobService $jobService,
        TaskService $taskService,
        JobTypeService $jobTypeService,
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        CrawlJobContainerService $crawlJobContainerService,
        UserService $userService,
        UrlFinder $urlFinder,
        StateService $stateService,
        UserAccountPlanService $userAccountPlanService,
        EntityManagerInterface $entityManager,
        CrawlJobUrlCollector $crawlJobUrlCollector
    ) {
        $this->jobService = $jobService;
        $this->taskService = $taskService;
        $this->jobTypeService = $jobTypeService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->userService = $userService;
        $this->urlFinder = $urlFinder;
        $this->stateService = $stateService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->entityManager = $entityManager;
        $this->crawlJobUrlCollector = $crawlJobUrlCollector;
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
        if (Job::STATE_RESOLVED !== $job->getState()->getName()) {
            throw new JobPreparationServiceException(
                'Job is in wrong state, currently "'.$job->getState()->getName().'"',
                JobPreparationServiceException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }

        $user = $job->getUser();

        $jobPreparingState = $this->stateService->get(Job::STATE_PREPARING);
        $job->setState($jobPreparingState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->processedUrls = array();

        $userAccountPlan = $this->userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();
        $urlsPerJobConstraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME
        );

        $this->jobUserAccountPlanEnforcementService->setUser($user);

        $urls = $this->collectUrlsForJob($job, $urlsPerJobConstraint->getLimit());

        if (empty($urls)) {
            $jobFailedNoSitemapState = $this->stateService->get(Job::STATE_FAILED_NO_SITEMAP);

            $job->setState($jobFailedNoSitemapState);

            if (!$this->userService->isPublicUser($user)) {
                if (!$this->crawlJobContainerService->hasForJob($job)) {
                    $crawlJobContainer = $this->crawlJobContainerService->getForJob($job);
                    $this->crawlJobContainerService->prepare($crawlJobContainer);
                }
            }

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        } else {
            if ($this->jobUserAccountPlanEnforcementService->isJobUrlLimitReached(count($urls))) {
                $this->jobService->addAmmendment(
                    $job,
                    'plan-url-limit-reached:discovered-url-count-' . count($urls),
                    $urlsPerJobConstraint
                );
                $urls = array_slice($urls, 0, $urlsPerJobConstraint->getLimit());
            }

            $this->prepareTasksFromCollectedUrls($job, $urls);

            $jobQueuedState = $this->stateService->get(Job::STATE_QUEUED);

            $job->setState($jobQueuedState);

            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime());
            $job->setTimePeriod($timePeriod);

            $this->entityManager->persist($job);
            $this->entityManager->flush();
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
        $this->processedUrls = [];
        $job = $crawlJobContainer->getParentJob();

        if (Job::STATE_FAILED_NO_SITEMAP !== $job->getState()->getName()) {
            throw new JobPreparationServiceException(
                'Job is in wrong state, currently "'.$job->getState()->getName().'"',
                JobPreparationServiceException::CODE_JOB_IN_WRONG_STATE_CODE
            );
        }

        $jobPreparingState = $this->stateService->get(Job::STATE_PREPARING);

        $job->setState($jobPreparingState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->crawlJobUrlCollector->setConstrainToAccountPlan(true);
        $urls = $this->crawlJobUrlCollector->getDiscoveredUrls($crawlJobContainer);

        if ($crawlJobContainer->getCrawlJob()->getAmmendments()->count()) {
            /* @var $ammendment \AppBundle\Entity\Job\Ammendment */

            foreach ($crawlJobContainer->getCrawlJob()->getAmmendments() as $ammendment) {
                /* @var Ammendment $ammendment */
                $constraint = $ammendment->getConstraint();

                if ($constraint->getName() == JobUserAccountPlanEnforcementService::URLS_PER_JOB_CONSTRAINT_NAME) {
                    $this->jobService->addAmmendment($job, $ammendment->getReason(), $constraint);
                }
            }
        }

        $this->prepareTasksFromCollectedUrls($job, $urls);

        $jobQueuedState = $this->stateService->get(Job::STATE_QUEUED);

        $job->setState($jobQueuedState);

        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $job->setTimePeriod($timePeriod);

        $this->entityManager->persist($job);
        $this->entityManager->flush();

        return self::RETURN_CODE_OK;
    }

    /**
     * @param Job $job
     * @param string[] $urls
     */
    private function prepareTasksFromCollectedUrls(Job $job, $urls)
    {
        $requestedTaskTypes = $job->getRequestedTaskTypes();

        $newTaskState = $this->stateService->get(Task::STATE_QUEUED);
        $jobParameters = $job->getParameters();

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

                    $taskParameters = new Parameters();
                    $taskParameters->merge($jobParameters);

                    if ($taskTypeOptions->getOptionCount()) {
                         $taskParameters->merge(new Parameters($taskTypeOptions->getOptions()));

                        $domainsToIgnore = $this->getDomainsToIgnore(
                            $taskTypeOptions,
                            $this->predefinedDomainsToIgnore
                        );

                        if (count($domainsToIgnore)) {
                            $taskParameters->set('domains-to-ignore', $domainsToIgnore);
                        }
                    }

                    $task->setParameters((string)$taskParameters);
                    $this->entityManager->persist($task);
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
        return JobTypeService::SINGLE_URL_NAME == $job->getType()->getName();
    }

    /**
     * @param Job $job
     * @param int $softLimit
     *
     * @return string[]
     */
    private function collectUrlsForJob(Job $job, $softLimit)
    {
        if ($this->isSingleUrlJob($job)) {
            return array($job->getWebsite()->getCanonicalUrl());
        }

        return $this->urlFinder->getUrls($job, $softLimit);
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
