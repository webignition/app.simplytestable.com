<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Resque\Job\Task\CancelCollectionJob;
use SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks\NotifyJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobSummaryFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeDomainsToIgnoreService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class JobController
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RetrievalService
     */
    private $jobRetrievalService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param RouterInterface $router
     * @param RetrievalService $jobRetrievalService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        RouterInterface $router,
        RetrievalService $jobRetrievalService,
        EntityManagerInterface $entityManager
    ) {
        $this->router = $router;
        $this->jobRetrievalService = $jobRetrievalService;
        $this->entityManager = $entityManager;
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
        $jobRepository = $this->entityManager->getRepository(Job::class);

        $website = $websiteService->get($site_root_url);

        /* @var Job $latestJob */
        $latestJob = null;

        $userHasTeam = $teamService->hasTeam($user);
        $userBelongsToTeam = $teamService->getMemberService()->belongsToTeam($user);

        if ($userHasTeam || $userBelongsToTeam) {
            $team = $teamService->getForUser($user);

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

        if (!$userService->isPublicUser($user)) {
            $latestJob = $jobRepository->findOneBy([
                'website' => $website,
                'user' => $user,
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
            throw new NotFoundHttpException();
        }

        return $this->createRedirectToJobStatus(
            $latestJob->getWebsite()->getCanonicalUrl(),
            $latestJob->getId()
        );
    }

    /**
     * @param UserService $userService
     * @param UserInterface $user
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return RedirectResponse
     */
    public function setPublicAction(
        UserService $userService,
        UserInterface $user,
        $site_root_url,
        $test_id
    ) {
        return $this->setIsPublic(
            $userService,
            $user,
            $site_root_url,
            $test_id,
            true
        );
    }

    /**
     * @param UserService $userService
     * @param UserInterface $user
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return RedirectResponse
     */
    public function setPrivateAction(
        UserService $userService,
        UserInterface $user,
        $site_root_url,
        $test_id
    ) {
        return $this->setIsPublic(
            $userService,
            $user,
            $site_root_url,
            $test_id,
            false
        );
    }

    /**
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return Response
     */
    public function isPublicAction(
        $site_root_url,
        $test_id
    ) {
        $jobRepository = $this->entityManager->getRepository(Job::class);

        return new Response(
            '',
            $jobRepository->getIsPublicByJobId($test_id) ? 200 : 404
        );
    }

    /**
     * @param UserService $userService
     * @param User|UserInterface $user
     * @param string $siteRootUrl
     * @param int $testId
     * @param bool $isPublic
     *
     * @return RedirectResponse
     */
    private function setIsPublic(
        UserService $userService,
        User $user,
        $siteRootUrl,
        $testId,
        $isPublic
    ) {
        if ($userService->isPublicUser($user)) {
            return $this->createRedirectToJobStatus($siteRootUrl, $testId);
        }

        $job = $this->retrieveJob($testId);

        if ($userService->isPublicUser($job->getUser())) {
            return $this->createRedirectToJobStatus($siteRootUrl, $testId);
        }

        if ($job->getIsPublic() !== $isPublic) {
            $job->setIsPublic(filter_var($isPublic, FILTER_VALIDATE_BOOLEAN));

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }

        return $this->createRedirectToJobStatus($siteRootUrl, $testId);
    }

    /**
     * @param JobSummaryFactory $jobSummaryFactory
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return JsonResponse|Response
     */
    public function statusAction(
        JobSummaryFactory $jobSummaryFactory,
        $site_root_url,
        $test_id
    ) {
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
     * @param string $site_root_url
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
        $site_root_url,
        $test_id
    ) {
        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $job = $this->retrieveJob($test_id);

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);

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
                    $site_root_url,
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
        $taskIds = $taskRepository->getIdsByJob($job);
        foreach ($taskIds as $taskId) {
            $tasksToDeAssign[] = array(
                'id' => $taskId
            );
        }

        $taskAwaitingCancellationState = $stateService->get(Task::STATE_AWAITING_CANCELLATION);

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
            $resqueQueueService->enqueue(new CancelCollectionJob(['ids' => implode(',', $taskIdsToCancel)]));
        }

        return new Response();
    }

    /**
     * @param TaskService $taskService
     * @param Request $request
     * @param string $site_root_url
     * @param int $test_id
     *
     * @return JsonResponse
     */
    public function tasksAction(
        TaskService $taskService,
        Request $request,
        $site_root_url,
        $test_id
    ) {
        $job = $this->retrieveJob($test_id);

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);

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
        $job = $this->retrieveJob($test_id);

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);

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
        $job = $this->retrieveJob($test_id);

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
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
        $url = $this->router->generate(
            'job_job_status',
            [
                'site_root_url' => $siteRootUrl,
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
