<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JobController extends BaseJobController
{
    protected $testId = null;

    /**
     * @param $site_root_url
     *
     * @return RedirectResponse|Response
     */
    public function latestAction($site_root_url)
    {
        $websiteService = $this->get('simplytestable.services.websiteservice');
        $jobService = $this->get('simplytestable.services.jobservice');
        $userService = $this->get('simplytestable.services.userservice');
        $teamService = $this->get('simplytestable.services.teamservice');

        $website = $websiteService->fetch($site_root_url);
        $latestJob = null;

        $userHasTeam = $teamService->hasTeam($this->getUser());
        $userBelongsToTeam = $teamService->getMemberService()->belongsToTeam($this->getUser());

        if ($userHasTeam || $userBelongsToTeam) {
            $team = $teamService->getForUser($this->getUser());

            $latestJob = $jobService->getEntityRepository()->findLatestByWebsiteAndUsers(
                $website,
                $teamService->getPeople($team)
            );

            if ($latestJob instanceof Job) {
                return $this->createRedirectToJobStatus(
                    $latestJob->getWebsite()->getCanonicalUrl(),
                    $latestJob->getId()
                );
            }
        }

        if (!$userService->isPublicUser($this->getUser())) {
            $latestJob = $jobService->getEntityRepository()->findLatestByWebsiteAndUsers(
                $website,
                array(
                    $this->getUser()
                )
            );

            if (!is_null($latestJob)) {
                return $this->createRedirectToJobStatus(
                    $latestJob->getWebsite()->getCanonicalUrl(),
                    $latestJob->getId()
                );
            }
        }

        $latestJob = $jobService->getEntityRepository()->findLatestByWebsiteAndUsers(
            $website,
            [
                $userService->getPublicUser()
            ]
        );

        if (is_null($latestJob)) {
            $response = new Response();
            $response->setStatusCode(404);
            return $response;
        }

        return $this->createRedirectToJobStatus(
            $latestJob->getWebsite()->getCanonicalUrl(),
            $latestJob->getId()
        );
    }

    public function setPublicAction($site_root_url, $test_id) {
        return $this->setIsPublic($site_root_url, $test_id, true);
    }

    public function setPrivateAction($site_root_url, $test_id) {
        return $this->setIsPublic($site_root_url, $test_id, false);
    }

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return Response
     */
    public function isPublicAction($site_root_url, $test_id)
    {
        $jobService = $this->get('simplytestable.services.jobservice');

        return new Response(
            '',
            $jobService->getIsPublic($test_id) ? 200 : 404
        );
    }

    private function setIsPublic($site_root_url, $test_id, $isPublic) {
        if ($this->getUserService()->isPublicUser($this->getUser())) {
            return $this->redirect($this->generateUrl('job_job_status', array(
                'site_root_url' => $site_root_url,
                'test_id' => $test_id
            ), true));
        }

        $this->getJobRetrievalService()->setUser($this->getUser());

        try {
            $job = $this->getJobRetrievalService()->retrieve($test_id);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        if ($job->getIsPublic() !== $isPublic) {
            $job->setIsPublic(filter_var($isPublic, FILTER_VALIDATE_BOOLEAN));
            $this->getJobService()->persistAndFlush($job);
        }

        return $this->redirect($this->generateUrl('job_job_status', array(
            'site_root_url' => $site_root_url,
            'test_id' => $job->getId()
        ), true));
    }


    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return Response
     */
    public function statusAction($site_root_url, $test_id)
    {
        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');
        $jobRetrievalService->setUser($this->getUser());

        try {
            $job = $jobRetrievalService->retrieve($test_id);
            $this->populateJob($job);

            $summary = $this->getSummary($job);

            return $this->sendResponse($summary);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }
    }

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return Response
     */
    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return Response
     */
    public function cancelAction($site_root_url, $test_id)
    {
        $isInMaintenanceReadOnlyState = $this->getApplicationStateService()->isInMaintenanceReadOnlyState();
        $isInMaintenanceBackupReadOnlyState = $this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState();

        if ($isInMaintenanceReadOnlyState || $isInMaintenanceBackupReadOnlyState) {
            return $this->sendServiceUnavailableResponse();
        }

        $jobRetrievalService = $this->get('simplytestable.services.job.retrievalservice');
        $jobService = $this->get('simplytestable.services.jobservice');
        $crawlJobContainerService = $this->get('simplytestable.services.crawljobcontainerservice');
        $jobPreparationService = $this->container->get('simplytestable.services.jobpreparationservice');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactoryservice');
        $taskService = $this->get('simplytestable.services.taskservice');
        $stateService = $this->get('simplytestable.services.stateservice');

        $jobRetrievalService->setUser($this->getUser());

        try {
            $job = $jobRetrievalService->retrieve($test_id);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        $this->testId = $test_id;

        $hasCrawlJob = $crawlJobContainerService->hasForJob($job);

        if ($hasCrawlJob) {
            $crawlJobContainer = $crawlJobContainerService->getForJob($job);

            $isParentJob = $crawlJobContainer->getParentJob() === $job;
            $isCrawlJob = $crawlJobContainer->getCrawlJob() === $job;

            if ($isParentJob) {
                $this->cancelAction($site_root_url, $crawlJobContainer->getCrawlJob()->getId());
            }

            if ($isCrawlJob) {
                $parentJob = $crawlJobContainerService->getForJob($job)->getParentJob();
                $this->setJobPrepartionDomainsToIgnoredFromJobTaskTypes($parentJob, $jobPreparationService);

                $jobPreparationService->prepareFromCrawl($this->getCrawlJobContainerService()->getForJob($parentJob));

                $resqueQueueService->enqueue(
                    $resqueJobFactory->create(
                        'tasks-notify'
                    )
                );
            }
        }

        $preCancellationState = clone $job->getState();

        $jobService->cancel($job);

        $jobStartingState = $stateService->fetch(JobService::STARTING_STATE);

        if ($preCancellationState->equals($jobStartingState)) {
            $resqueQueueService->dequeue(
                $resqueJobFactory->create(
                    'job-prepare',
                    ['id' => $job->getId()]
                )
            );
        }

        $tasksToDeAssign = array();
        $taskIds = $taskService->getEntityRepository()->getIdsByJob($job);
        foreach ($taskIds as $taskId) {
            $tasksToDeAssign[] = array(
                'id' => $taskId
            );
        }

        $tasksAwaitingCancellation = $taskService->getAwaitingCancellationByJob($job);
        $taskIdsToCancel = array();

        foreach ($tasksAwaitingCancellation as $task) {
            $taskIdsToCancel[] = $task->getId();
        }

        if (count($taskIdsToCancel) > 0) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'task-cancel-collection',
                    ['ids' => implode(',', $taskIdsToCancel)]
                )
            );
        }

        return $this->sendSuccessResponse();
    }

    public function tasksAction($site_root_url, $test_id) {
        $this->getJobRetrievalService()->setUser($this->getUser());

        try {
            $job = ($this->getJobRetrievalService()->retrieve($test_id));
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        $taskIds = $this->getRequestTaskIds();
        $tasks = $this->getTaskService()->getEntityRepository()->getCollectionByJobAndId($job, $taskIds);

        foreach ($tasks as $task) {
            /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
            if (!$this->getTaskService()->isFinished($task)) {
                $task->setOutput(null);
            }
        }

        return $this->sendResponse($tasks);
    }


    public function taskIdsAction($site_root_url, $test_id) {
        $this->getJobRetrievalService()->setUser($this->getUser());

        try {
            $job = ($this->getJobRetrievalService()->retrieve($test_id));
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($job);

        return $this->sendResponse($taskIds);
    }


    public function listUrlsAction($site_root_url, $test_id) {
        $this->getJobRetrievalService()->setUser($this->getUser());

        try {
            $job = ($this->getJobRetrievalService()->retrieve($test_id));
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        return $this->sendResponse($this->getTaskService()->getUrlsByJob($job));
    }



    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    protected function getJobByUser() {
        $job = $this->getJobService()->getEntityRepository()->findOneBy(array(
            'id' => $this->testId,
            'user' => array(
                $this->getUser(),
                $this->getUserService()->getPublicUser()
            )
        ));

        if (is_null($job)) {
            return false;
        }

        return $this->populateJob($job);
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    protected function getJobByVisibilityOrUser() {
        // Check for jobs that are public by owner
        $publicJob = $this->getJobService()->getEntityRepository()->findOneBy(array(
            'id' => $this->testId,
            'isPublic' => true
        ));

        if (!is_null($publicJob)) {
            return $this->populateJob($publicJob);
        }

        return $this->getJobByUser();
    }


    /**
     *
     * @return array|null
     */
    private function getRequestTaskIds() {
        $requestTaskIds = $this->getRequestValue('taskIds');
        $taskIds = array();

        if (substr_count($requestTaskIds, ':')) {
            $rangeLimits = explode(':', $requestTaskIds);

            for ($i = $rangeLimits[0]; $i<=$rangeLimits[1]; $i++) {
                $taskIds[] = $i;
            }
        } else {
            $rawRequestTaskIds = explode(',', $requestTaskIds);

            foreach ($rawRequestTaskIds as $requestTaskId) {
                if (ctype_digit($requestTaskId)) {
                    $taskIds[] = (int)$requestTaskId;
                }
            }
        }

        return (count($taskIds) > 0) ? $taskIds : null;
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */
    private function getJobPreparationService() {
        return $this->container->get('simplytestable.services.jobpreparationservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\QueueService
     */
    private function getResqueQueueService() {
        return $this->container->get('simplytestable.services.resque.queueService');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Resque\JobFactoryService
     */
    private function getResqueJobFactoryService() {
        return $this->container->get('simplytestable.services.resque.jobFactoryService');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    private function getTeamService() {
        return $this->get('simplytestable.services.teamservice');
    }

    /**
     * @param string $siteRootUrl
     * @param int $testId
     *
     * @return RedirectResponse
     */
    private function createRedirectToJobStatus($siteRootUrl, $testId)
    {
        return $this->redirect(
            $this->generateUrl(
                'job_job_status',
                [
                    'site_root_url' => $siteRootUrl,
                    'test_id' => $testId
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }

    /**
     * @param Job $job
     * @param JobPreparationService $jobPreparationService
     */
    private function setJobPrepartionDomainsToIgnoredFromJobTaskTypes(
        Job $job,
        JobPreparationService $jobPreparationService
    ) {
        foreach ($job->getRequestedTaskTypes() as $taskType) {
            /* @var Type $taskType */
            $taskTypeNameKey = strtolower(str_replace(' ', '-', $taskType->getName()));
            $taskTypeParameterDomainsToIgnoreKey = $taskTypeNameKey . '-domains-to-ignore';

            if ($this->container->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                $jobPreparationService->setPredefinedDomainsToIgnore(
                    $taskType,
                    $this->container->getParameter($taskTypeParameterDomainsToIgnoreKey)
                );
            }
        }
    }
}
