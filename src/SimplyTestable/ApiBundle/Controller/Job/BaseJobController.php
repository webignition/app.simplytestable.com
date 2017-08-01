<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Controller\ApiController;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;

abstract class BaseJobController extends ApiController
{
    /**
     * @param Job $job
     *
     * @return array
     */
    protected function getSummary(Job $job)
    {
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $jobRejectionReasonService = $this->container->get('simplytestable.services.jobrejectionreasonservice');

        $jobTaskTypeOptions = [];

        foreach ($job->getTaskTypeOptions() as $taskTypeOptions) {
            /* @var $taskTypeOptions TaskTypeOptions */
            $jobTaskTypeOptions[$taskTypeOptions->getTaskType()->getName()] = $taskTypeOptions->getOptions();
        }

        $isPublic = $userService->isPublicUser($job->getUser())
            ? true
            : $job->getIsPublic();

        $taskRepository = $taskService->getEntityRepository();

        $errorCount = $taskRepository->getErrorCountByJob($job);
        $warningCount = $taskRepository->getWarningCountByJob($job);

        $jobSummary = [
            'id' => $job->getId(),
            'user' => $job->getPublicSerializedUser(),
            'website' => $job->getPublicSerializedWebsite(),
            'state' => $job->getPublicSerializedState(),
            'time_period' => $job->getTimePeriod(),
            'url_count' => $job->getUrlCount(),
            'task_count' => $taskService->getCountByJob($job),
            'task_count_by_state' => $this->getTaskCountByState($job),
            'task_types' => $job->getRequestedTaskTypes(),
            'errored_task_count' => $jobService->getErroredTaskCount($job),
            'cancelled_task_count' => $jobService->getCancelledTaskCount($job),
            'skipped_task_count' => $jobService->getSkippedTaskCount($job),
            'warninged_task_count' => $jobService->getWarningedTaskCount($job),
            'task_type_options' => $jobTaskTypeOptions,
            'type' => $job->getPublicSerializedType(),
            'is_public' => $isPublic,
            'parameters' => $job->getParameters(),
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'owners' => $this->getSerializedOwners($job)
        ];

        if ($jobService->isRejected($job)) {
            $jobSummary['rejection'] = $jobRejectionReasonService->getForJob($job);
        }

        if (!is_null($job->getAmmendments()) && $job->getAmmendments()->count() > 0) {
            $jobSummary['ammendments'] = $job->getAmmendments();
        }

        if ($crawlJobContainerService->hasForJob($job)) {
            $crawlJobContainer = $crawlJobContainerService->getForJob($job);
            $crawlJob = $crawlJobContainer->getCrawlJob();

            $jobSummary['crawl'] = array(
                'id' => $crawlJob->getId(),
                'state' => $crawlJob->getPublicSerializedState(),
                'processed_url_count' => count($crawlJobContainerService->getProcessedUrls($crawlJobContainer)),
                'discovered_url_count' => count($crawlJobContainerService->getDiscoveredUrls($crawlJobContainer, true)),
            );

            $userAccountPlan = $userAccountPlanService->getForUser($job->getUser())->getPlan();

            if ($userAccountPlan->hasConstraintNamed('urls_per_job')) {
                $jobSummary['crawl']['limit'] = $userAccountPlan->getConstraintNamed('urls_per_job')->getLimit();
            }
        }

        return $jobSummary;
    }

    /**
     * @param Job $job
     *
     * @return Job
     */
    protected function populateJob(Job $job)
    {
        $this->getTaskService()->getCountByJobAndState($job, $this->getTaskService()->getCompletedState());
        $job->setUrlCount($this->container->get('simplytestable.services.taskservice')->getUrlCountByJob($job));

        return $job;
    }

    /**
     * @param Job $job
     *
     * @return array
     */
    private function getTaskCountByState(Job $job)
    {
        $availableStateNames = $this->getTaskService()->getAvailableStateNames();
        $taskCountByState = array();

        foreach ($availableStateNames as $stateName) {
            $stateShortName = str_replace('task-', '', $stateName);
            $methodName = $this->stateNameToStateRetrievalMethodName($stateShortName);
            $taskCountByState[$stateShortName] = $this->getTaskService()->getCountByJobAndState(
                $job,
                $this->getTaskService()->$methodName()
            );
        }

        return $taskCountByState;
    }

    /**
     * @param string $stateName
     *
     * @return string
     */
    private function stateNameToStateRetrievalMethodName($stateName)
    {
        $methodName = $stateName;

        $methodName = str_replace('-', ' ', $methodName);
        $methodName = ucwords($methodName);
        $methodName = str_replace(' ', '', $methodName);

        return 'get' . $methodName . 'State';
    }

    /**
     * @param Job $job
     *
     * @return string[]
     */
    private function getSerializedOwners(Job $job)
    {
        $owners = $this->getOwners($job);
        $serializedOwners = [];

        foreach ($owners as $owner) {
            $serializedOwners[] = $owner->getUsername();
        }

        return $serializedOwners;
    }

    /**
     * @param Job $job
     *
     * @return User[]
     */
    private function getOwners(Job $job)
    {
        if (!$this->getTeamService()->hasForUser($this->getUser())) {
            return [
                $job->getUser()
            ];
        }

        $team = $this->getTeamService()->getForUser($job->getUser());
        $members = $this->getTeamService()->getMemberService()->getMembers($team);

        $owners = [
            $team->getLeader()
        ];

        foreach ($members as $member) {
            if (!$this->userCollectionContainsUser($owners, $member->getUser())) {
                $owners[] = $member->getUser();
            }
        }

        return $owners;
    }

    /**
     * @param User[] $users
     * @param User $user
     *
     * @return bool
     */
    private function userCollectionContainsUser(array $users, User $user)
    {
        foreach ($users as $currentUser) {
            if ($user->equals($currentUser)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return JobService
     */
    protected function getJobService()
    {
        return $this->get('simplytestable.services.jobservice');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->get('simplytestable.services.taskservice');
    }

    /**
     * @return CrawlJobContainerService
     */
    protected function getCrawlJobContainerService()
    {
        return $this->get('simplytestable.services.crawljobcontainerservice');
    }

    /**
     * @return JobTypeService
     */
    protected function getJobTypeService()
    {
        return $this->get('simplytestable.services.JobTypeService');
    }

    /**
     * @return RetrievalService
     */
    protected function getJobRetrievalService()
    {
        return $this->get('simplytestable.services.job.retrievalservice');
    }

    /**
     * @return TeamService
     */
    private function getTeamService() {
        return $this->container->get('simplytestable.services.teamservice');
    }
}
