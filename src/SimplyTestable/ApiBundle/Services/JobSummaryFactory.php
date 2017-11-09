<?php

namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\Job\Summary\CrawlSummary;
use SimplyTestable\ApiBundle\Model\Job\Summary\Summary as JobSummary;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;

class JobSummaryFactory
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var CrawlJobContainerService
     */
    private $crawlJobContainerService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var EntityRepository
     */
    private $jobRejectionReasonRepository;

    /**
     * @var CrawlJobUrlCollector
     */
    private $crawlJobUrlCollector;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TaskService $taskService
     * @param StateService $stateService
     * @param JobService $jobService
     * @param TeamService $teamService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param UserAccountPlanService $userAccountPlanService
     * @param TaskRepository $taskRepository
     * @param EntityRepository $jobRejectionReasonRepository
     * @param CrawlJobUrlCollector $crawlJobUrlCollector
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        StateService $stateService,
        JobService $jobService,
        TeamService $teamService,
        CrawlJobContainerService $crawlJobContainerService,
        UserAccountPlanService $userAccountPlanService,
        TaskRepository $taskRepository,
        EntityRepository $jobRejectionReasonRepository,
        CrawlJobUrlCollector $crawlJobUrlCollector
    ) {
        $this->entityManager = $entityManager;
        $this->taskService = $taskService;
        $this->stateService = $stateService;
        $this->jobService = $jobService;
        $this->teamService = $teamService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->taskRepository = $taskRepository;
        $this->jobRejectionReasonRepository = $jobRejectionReasonRepository;
        $this->crawlJobUrlCollector = $crawlJobUrlCollector;
    }

    /**
     * @param Job $job
     *
     * @return JobSummary
     */
    public function create(Job $job)
    {
        if (is_null($job->getUrlCount())) {
            $job->setUrlCount($this->taskRepository->findUrlCountByJob($job));
        }

        $isPublic = $job->getUser()->getEmail() === UserService::PUBLIC_USER_EMAIL_ADDRESS
            ? true
            : $job->getIsPublic();

        $jobSummary = new JobSummary(
            $job,
            $this->taskRepository->getCountByJob($job),
            $this->createTaskCountByState(
                $job,
                $this->stateService->getCollection($this->taskService->getAvailableStateNames())
            ),
            $this->jobService->getCountOfTasksWithErrors($job),
            $this->jobService->getCountOfTasksWithWarnings($job),
            $this->jobService->getSkippedTaskCount($job),
            $this->jobService->getCancelledTaskCount($job),
            $isPublic,
            $this->taskRepository->getErrorCountByJob($job),
            $this->taskRepository->getWarningCountByJob($job),
            $this->getOwners($job)
        );

        if (JobService::REJECTED_STATE === $job->getState()->getName()) {
            /* @var RejectionReason $jobRejectionReason */
            $jobRejectionReason = $this->jobRejectionReasonRepository->findOneBy([
                'job' => $job,
            ]);

            $jobSummary->setRejectionReason($jobRejectionReason);
        }

        $ammendments = $job->getAmmendments()->toArray();
        if (!empty($job->getAmmendments())) {
            $jobSummary->setAmmendments($ammendments);
        }

        if ($this->crawlJobContainerService->hasForJob($job)) {
            $crawlJobContainer = $this->crawlJobContainerService->getForJob($job);
            $crawlJob = $crawlJobContainer->getCrawlJob();

            $userAccountPlan = $this->userAccountPlanService->getForUser($job->getUser())->getPlan();
            $urlsPerJobConstraint = $userAccountPlan->getConstraintNamed('urls_per_job');

            $limit = empty($urlsPerJobConstraint)
                ? null
                : $urlsPerJobConstraint->getLimit();

            $crawlSummary = new CrawlSummary(
                $crawlJob,
                count($this->crawlJobContainerService->getProcessedUrls($crawlJobContainer)),
                count($this->crawlJobUrlCollector->getDiscoveredUrls($crawlJobContainer)),
                $limit
            );

            $jobSummary->setCrawlSummary($crawlSummary);
        }

        return $jobSummary;
    }

    /**
     * @param Job $job
     * @param State[] $taskStates
     *
     * @return array
     */
    private function createTaskCountByState(Job $job, $taskStates)
    {
        $taskCountByState = [];

        foreach ($taskStates as $taskState) {
            $taskStateShortName = str_replace('task-', '', $taskState->getName());
            $taskCountByState[$taskStateShortName] = $this->taskRepository->getCountByJobAndStates($job, [$taskState]);
        }

        return $taskCountByState;
    }

    /**
     * @param Job $job
     *
     * @return User[]
     */
    private function getOwners(Job $job)
    {
        $user = $job->getUser();

        if (!$this->teamService->hasForUser($user)) {
            return [
                $job->getUser()
            ];
        }

        $team = $this->teamService->getForUser($user);
        $members = $this->teamService->getMemberService()->getMembers($team);

        $owners = [
            $team->getLeader()
        ];

        foreach ($members as $member) {
            $owners[] = $member->getUser();
        }

        return $owners;
    }
}
