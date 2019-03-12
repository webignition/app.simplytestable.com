<?php

namespace App\Controller\Job;

use App\Repository\JobRepository;
use App\Services\Job\AuthorisationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Resque\Job\Task\CancelCollectionJob;
use App\Resque\Job\Worker\Tasks\NotifyJob;
use App\Services\ApplicationStateService;
use App\Services\CrawlJobContainerService;
use App\Services\Job\RetrievalService;
use App\Services\JobPreparationService;
use App\Services\JobService;
use App\Services\JobSummaryFactory;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\TaskTypeDomainsToIgnoreService;
use App\Services\Team\Service as TeamService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class JobController
{
    private $router;
    private $jobRetrievalService;
    private $entityManager;
    private $jobRepository;
    private $taskRepository;
    private $jobAuthorisationService;

    public function __construct(
        RouterInterface $router,
        RetrievalService $jobRetrievalService,
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository,
        TaskRepository $taskRepository,
        AuthorisationService $jobAuthorisationService
    ) {
        $this->router = $router;
        $this->jobRetrievalService = $jobRetrievalService;
        $this->entityManager = $entityManager;
        $this->jobRepository = $jobRepository;
        $this->taskRepository = $taskRepository;
        $this->jobAuthorisationService = $jobAuthorisationService;
    }

    /**
     * @param WebSiteService $websiteService
     * @param UserService $userService
     * @param TeamService $teamService
     * @param UserInterface|User $user
     * @param string $site_root_url
     *
     * @return RedirectResponse|Response
     */
    public function latestAction(
        WebSiteService $websiteService,
        UserService $userService,
        TeamService $teamService,
        UserInterface $user,
        $site_root_url
    ) {
        $website = $websiteService->get($site_root_url);

        /* @var Job $latestJob */
        $latestJob = null;

        $userHasTeam = $teamService->hasTeam($user);
        $userBelongsToTeam = $teamService->getMemberService()->belongsToTeam($user);

        if ($userHasTeam || $userBelongsToTeam) {
            $team = $teamService->getForUser($user);

            $latestJob = $this->jobRepository->findOneBy([
                'website' => $website,
                'user' => $teamService->getPeople($team),
            ], [
                'id' => 'DESC',
            ]);

            if ($latestJob instanceof Job) {
                return $this->createRedirectToJobStatus(
                    $latestJob->getId()
                );
            }
        }

        if (!$userService->isPublicUser($user)) {
            $latestJob = $this->jobRepository->findOneBy([
                'website' => $website,
                'user' => $user,
            ], [
                'id' => 'DESC',
            ]);

            if (!is_null($latestJob)) {
                return $this->createRedirectToJobStatus(
                    $latestJob->getId()
                );
            }
        }

        $latestJob = $this->jobRepository->findOneBy([
            'website' => $website,
            'user' => $userService->getPublicUser()
        ], [
            'id' => 'DESC',
        ]);

        if (is_null($latestJob)) {
            throw new NotFoundHttpException();
        }

        return $this->createRedirectToJobStatus(
            $latestJob->getId()
        );
    }

    /**
     * @param UserService $userService
     * @param UserInterface $user
     * @param int $test_id
     *
     * @return RedirectResponse
     */
    public function setPublicAction(UserService $userService, UserInterface $user, $test_id)
    {
        return $this->setIsPublic(
            $userService,
            $user,
            $test_id,
            true
        );
    }

    /**
     * @param UserService $userService
     * @param UserInterface $user
     * @param int $test_id
     *
     * @return RedirectResponse
     */
    public function setPrivateAction(UserService $userService, UserInterface $user, $test_id)
    {
        return $this->setIsPublic(
            $userService,
            $user,
            $test_id,
            false
        );
    }

    /**
     * @param UserService $userService
     * @param User|UserInterface $user
     * @param int $testId
     * @param bool $isPublic
     *
     * @return RedirectResponse
     */
    private function setIsPublic(
        UserService $userService,
        User $user,
        $testId,
        $isPublic
    ) {
        if ($userService->isPublicUser($user)) {
            return $this->createRedirectToJobStatus($testId);
        }

        $job = $this->retrieveJob($testId);

        if ($userService->isPublicUser($job->getUser())) {
            return $this->createRedirectToJobStatus($testId);
        }

        if ($job->getIsPublic() !== $isPublic) {
            $job->setIsPublic(filter_var($isPublic, FILTER_VALIDATE_BOOLEAN));

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }

        return $this->createRedirectToJobStatus($testId);
    }

    /**
     * @param JobSummaryFactory $jobSummaryFactory
     * @param int $test_id
     *
     * @return JsonResponse|Response
     */
    public function statusAction(JobSummaryFactory $jobSummaryFactory, $test_id)
    {
        $job = $this->retrieveJob($test_id);
        $jobSummary = $jobSummaryFactory->create($job);

        return new JsonResponse($jobSummary);
    }

    /**
     * @param ApplicationStateService $applicationStateService
     * @param JobService $jobService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param JobPreparationService $jobPreparationService
     * @param ResqueQueueService $resqueQueueService
     * @param StateService $stateService
     * @param TaskTypeDomainsToIgnoreService $taskTypeDomainsToIgnoreService
     * @param int $test_id
     *
     * @return Response
     */
    public function cancelAction(
        ApplicationStateService $applicationStateService,
        JobService $jobService,
        CrawlJobContainerService $crawlJobContainerService,
        JobPreparationService $jobPreparationService,
        ResqueQueueService $resqueQueueService,
        StateService $stateService,
        TaskTypeDomainsToIgnoreService $taskTypeDomainsToIgnoreService,
        $test_id
    ) {
        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $job = $this->retrieveJob($test_id);

        $hasCrawlJob = $crawlJobContainerService->hasForJob($job);

        if ($hasCrawlJob) {
            $crawlJobContainer = $crawlJobContainerService->getForJob($job);

            $isParentJob = $crawlJobContainer->getParentJob() === $job;
            $isCrawlJob = $crawlJobContainer->getCrawlJob() === $job;

            if ($isParentJob) {
                $this->cancelAction(
                    $applicationStateService,
                    $jobService,
                    $crawlJobContainerService,
                    $jobPreparationService,
                    $resqueQueueService,
                    $stateService,
                    $taskTypeDomainsToIgnoreService,
                    $crawlJobContainer->getCrawlJob()->getId()
                );
            }

            if ($isCrawlJob) {
                $parentJob = $crawlJobContainerService->getForJob($job)->getParentJob();

                foreach ($parentJob->getRequestedTaskTypes() as $taskType) {
                    $taskTypeDomainsToIgnore = $taskTypeDomainsToIgnoreService->getForTaskType($taskType);

                    if (!empty($taskTypeDomainsToIgnore)) {
                        $jobPreparationService->setPredefinedDomainsToIgnore(
                            $taskType,
                            $taskTypeDomainsToIgnore
                        );
                    }
                }

                $jobPreparationService->prepareFromCrawl($crawlJobContainerService->getForJob($parentJob));
                $resqueQueueService->enqueue(new NotifyJob());
            }
        }

        $jobService->cancel($job);

        $tasksToDeAssign = array();
        $taskIds = $this->taskRepository->getIdsByJob($job);
        foreach ($taskIds as $taskId) {
            $tasksToDeAssign[] = array(
                'id' => $taskId
            );
        }

        $taskAwaitingCancellationState = $stateService->get(Task::STATE_AWAITING_CANCELLATION);

        /* @var Task[] $tasksAwaitingCancellation */
        $tasksAwaitingCancellation = $this->taskRepository->findBy([
            'job' => $job,
            'state' => $taskAwaitingCancellationState,
        ]);

        $taskIdsToCancel = array();

        foreach ($tasksAwaitingCancellation as $task) {
            $taskIdsToCancel[] = $task->getId();
        }

        if (count($taskIdsToCancel) > 0) {
            $resqueQueueService->enqueue(new CancelCollectionJob(['ids' => implode(',', $taskIdsToCancel)]));
        }

        return new Response();
    }

    /**
     * @param TaskService $taskService
     * @param Request $request
     * @param int $test_id
     *
     * @return JsonResponse
     */
    public function tasksAction(TaskService $taskService, Request $request, $test_id)
    {
        $job = $this->retrieveJob($test_id);
        $taskIds = $this->getRequestTaskIds($request);

        $taskFindByCriteria = [
            'job' => $job,
        ];

        if (!empty($taskIds)) {
            $taskFindByCriteria['id'] = $taskIds;
        }

        $tasks = $this->taskRepository->findBy($taskFindByCriteria);

        foreach ($tasks as $task) {
            /* @var $task \App\Entity\Task\Task */
            if (!$taskService->isFinished($task)) {
                $task->setOutput(null);
            }
        }

        return new JsonResponse($tasks);
    }

    /**
     * @param int $test_id
     *
     * @return Response
     */
    public function taskIdsAction($test_id)
    {
        $job = $this->retrieveJob($test_id);
        $taskIds = $this->taskRepository->getIdsByJob($job);

        return new JsonResponse($taskIds);
    }

    public function isAuthorisedAction($test_id): JsonResponse
    {
        return new JsonResponse($this->jobAuthorisationService->isAuthorised((int) $test_id));
    }

    /**
     * @param Request $request
     *
     * @return int[]
     */
    private function getRequestTaskIds(Request $request): array
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

        return $taskIds;
    }

    /**
     * @param int $testId
     *
     * @return RedirectResponse
     */
    private function createRedirectToJobStatus($testId)
    {
        $url = $this->router->generate(
            'job_job_status',
            [
                'test_id' => $testId
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new RedirectResponse($url);
    }

    /**
     * @param int $testId
     *
     * @return Job
     */
    private function retrieveJob($testId)
    {
        try {
            return $this->jobRetrievalService->retrieve($testId);
        } catch (JobRetrievalServiceException $jobRetrievalServiceException) {
            throw new AccessDeniedHttpException();
        }
    }
}
