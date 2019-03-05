<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\Job;
use App\Entity\Job\RejectionReason;
use App\Entity\State;
use App\Entity\Task\Task;
use App\Entity\User;
use App\Model\Job\Summary\CrawlSummary;
use App\Model\Job\Summary\Summary as JobSummary;
use App\Repository\TaskRepository;
use App\Services\Team\Service as TeamService;

class JobSummaryFactory
{
    private $taskService;
    private $stateService;
    private $jobService;
    private $teamService;
    private $crawlJobContainerService;
    private $userAccountPlanService;
    private $taskRepository;
    private $crawlJobUrlCollector;

    /**
     * @var EntityRepository
     */
    private $jobRejectionReasonRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        TaskService $taskService,
        StateService $stateService,
        JobService $jobService,
        TeamService $teamService,
        CrawlJobContainerService $crawlJobContainerService,
        UserAccountPlanService $userAccountPlanService,
        CrawlJobUrlCollector $crawlJobUrlCollector,
        TaskRepository $taskRepository
    ) {
        $this->taskService = $taskService;
        $this->stateService = $stateService;
        $this->jobService = $jobService;
        $this->teamService = $teamService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->crawlJobUrlCollector = $crawlJobUrlCollector;

        $this->taskRepository = $taskRepository;
        $this->jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);
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

        if (Job::STATE_REJECTED === $job->getState()->getName()) {
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

            $processedUrls = $this->taskRepository->findUrlsByJobAndState(
                $crawlJobContainer->getCrawlJob(),
                $this->stateService->get(Task::STATE_COMPLETED)
            );

            $crawlSummary = new CrawlSummary(
                $crawlJob,
                count($processedUrls),
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
