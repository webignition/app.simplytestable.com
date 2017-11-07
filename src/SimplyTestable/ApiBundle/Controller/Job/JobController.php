<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JobController extends Controller
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
        $userService = $this->get('simplytestable.services.userservice');
        $teamService = $this->get('simplytestable.services.teamservice');
        $jobRepository = $this->container->get('simplytestable.repository.job');

        $website = $websiteService->fetch($site_root_url);
        $latestJob = null;

        $userHasTeam = $teamService->hasTeam($this->getUser());
        $userBelongsToTeam = $teamService->getMemberService()->belongsToTeam($this->getUser());

        if ($userHasTeam || $userBelongsToTeam) {
            $team = $teamService->getForUser($this->getUser());

            $latestJob = $jobRepository->findOneBy([
                'website' => $website,
                'user' => $teamService->getPeople($team),
            ], [
                'id' => 'DESC',
            ]);

            if ($latestJob instanceof Job) {
                return $this->createRedirectToJobStatus(
                    $latestJob->getWebsite()->getCanonicalUrl(),
                    $latestJob->getId()
                );
            }
        }

        if (!$userService->isPublicUser($this->getUser())) {
            $latestJob = $jobRepository->findOneBy([
                'website' => $website,
                'user' => $this->getUser(),
            ], [
                'id' => 'DESC',
            ]);

            if (!is_null($latestJob)) {
                return $this->createRedirectToJobStatus(
                    $latestJob->getWebsite()->getCanonicalUrl(),
                    $latestJob->getId()
                );
            }
        }

        $latestJob = $jobRepository->findOneBy([
            'website' => $website,
            'user' => $userService->getPublicUser()
        ], [
            'id' => 'DESC',
        ]);

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

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return RedirectResponse|Response
     */
    public function setPublicAction($site_root_url, $test_id)
    {
        return $this->setIsPublic($site_root_url, $test_id, true);
    }

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return RedirectResponse|Response
     */
    public function setPrivateAction($site_root_url, $test_id)
    {
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
        /* @var JobRepository $jobRepository */
        $jobRepository = $this->container->get('simplytestable.repository.job');

        return new Response(
            '',
            $jobRepository->getIsPublicByJobId($test_id) ? 200 : 404
        );
    }

    /**
     * @param string $siteRootUrl
     * @param int $testId
     * @param bool $isPublic
     *
     * @return RedirectResponse|Response
     */
    private function setIsPublic($siteRootUrl, $testId, $isPublic)
    {
        $userService = $this->get('simplytestable.services.userservice');
        $jobRetrievalService = $this->get('simplytestable.services.job.retrievalservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        if ($userService->isPublicUser($this->getUser())) {
            return $this->createRedirectToJobStatus($siteRootUrl, $testId);
        }

        $jobRetrievalService->setUser($this->getUser());

        try {
            $job = $jobRetrievalService->retrieve($testId);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);

            return $response;
        }

        if ($userService->isPublicUser($job->getUser())) {
            return $this->createRedirectToJobStatus($siteRootUrl, $testId);
        }

        if ($job->getIsPublic() !== $isPublic) {
            $job->setIsPublic(filter_var($isPublic, FILTER_VALIDATE_BOOLEAN));

            $entityManager->persist($job);
            $entityManager->flush();
        }

        return $this->createRedirectToJobStatus($siteRootUrl, $testId);
    }

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return JsonResponse|Response
     */
    public function statusAction($site_root_url, $test_id)
    {
        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');
        $jobSummaryFactory = $this->container->get('simplytestable.services.jobsummaryfactory');

        $jobRetrievalService->setUser($this->getUser());

        try {
            $job = $jobRetrievalService->retrieve($test_id);
            $jobSummary = $jobSummaryFactory->create($job);

            return new JsonResponse($jobSummary);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            throw new AccessDeniedHttpException();
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
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $jobRetrievalService = $this->get('simplytestable.services.job.retrievalservice');
        $jobService = $this->get('simplytestable.services.jobservice');
        $crawlJobContainerService = $this->get('simplytestable.services.crawljobcontainerservice');
        $jobPreparationService = $this->container->get('simplytestable.services.jobpreparationservice');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $taskService = $this->get('simplytestable.services.taskservice');
        $stateService = $this->get('simplytestable.services.stateservice');

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->container->get('simplytestable.repository.task');

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
                $this->setJobPreparationDomainsToIgnoredFromJobTaskTypes($parentJob, $jobPreparationService);

                $jobPreparationService->prepareFromCrawl($crawlJobContainerService->getForJob($parentJob));

                $resqueQueueService->enqueue(
                    $resqueJobFactory->create(
                        'tasks-notify'
                    )
                );
            }
        }

        $jobService->cancel($job);

        $tasksToDeAssign = array();
        $taskIds = $taskRepository->getIdsByJob($job);
        foreach ($taskIds as $taskId) {
            $tasksToDeAssign[] = array(
                'id' => $taskId
            );
        }

        $taskAwaitingCancellationState = $stateService->get(TaskService::AWAITING_CANCELLATION_STATE);

        /* @var Task[] $tasksAwaitingCancellation */
        $tasksAwaitingCancellation = $taskRepository->findBy([
            'job' => $job,
            'state' => $taskAwaitingCancellationState,
        ]);

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

        return new Response();
    }

    /**
     * @param Request $request
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return JsonResponse|Response
     */
    public function tasksAction(Request $request, $site_root_url, $test_id)
    {
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->container->get('simplytestable.repository.task');

        $jobRetrievalService->setUser($this->getUser());

        try {
            $job = ($jobRetrievalService->retrieve($test_id));
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        $taskIds = $this->getRequestTaskIds($request);

        $taskFindByCriteria = [
            'job' => $job,
        ];

        if (!empty($taskIds)) {
            $taskFindByCriteria['id'] = $taskIds;
        }

        $tasks = $taskRepository->findBy($taskFindByCriteria);

        foreach ($tasks as $task) {
            /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
            if (!$taskService->isFinished($task)) {
                $task->setOutput(null);
            }
        }

        return new JsonResponse($tasks);
    }

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return Response
     */
    public function taskIdsAction($site_root_url, $test_id)
    {
        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->container->get('simplytestable.repository.task');

        $jobRetrievalService->setUser($this->getUser());

        try {
            $job = ($jobRetrievalService->retrieve($test_id));
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        $taskIds = $taskRepository->getIdsByJob($job);

        return new JsonResponse($taskIds);
    }

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return Response
     */
    public function listUrlsAction($site_root_url, $test_id)
    {
        $jobRetrievalService = $this->container->get('simplytestable.services.job.retrievalservice');

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->container->get('simplytestable.repository.task');

        $jobRetrievalService->setUser($this->getUser());

        try {
            $job = ($jobRetrievalService->retrieve($test_id));
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;
        }

        $urls = $taskRepository->findUrlsByJob($job);

        return new JsonResponse($urls);
    }

    /**
     * @param Request $request
     *
     * @return \int[]|null
     */
    private function getRequestTaskIds(Request $request)
    {
        $requestTaskIds = $request->request->get('taskIds');

        $taskIds = [];

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
    private function setJobPreparationDomainsToIgnoredFromJobTaskTypes(
        Job $job,
        JobPreparationService $jobPreparationService
    ) {
        foreach ($job->getRequestedTaskTypes() as $taskType) {
            /* @var Type $taskType */
            $taskTypeNameKey = strtolower(str_replace(' ', '_', $taskType->getName()));
            $taskTypeParameterDomainsToIgnoreKey = $taskTypeNameKey . '_domains_to_ignore';

            if ($this->container->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                $jobPreparationService->setPredefinedDomainsToIgnore(
                    $taskType,
                    $this->container->getParameter($taskTypeParameterDomainsToIgnoreKey)
                );
            }
        }
    }
}
