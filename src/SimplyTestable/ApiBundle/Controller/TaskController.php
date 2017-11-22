<?php

namespace SimplyTestable\ApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeDomainsToIgnoreService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskController
{
    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TaskTypeService $taskTypeService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TaskTypeService $taskTypeService
    ) {
        $this->entityManager = $entityManager;
        $this->taskTypeService = $taskTypeService;
    }

    /**
     * @param ApplicationStateService $applicationStateService
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param CompleteRequestFactory $completeRequestFactory
     * @param TaskService $taskService
     * @param JobService $jobService
     * @param JobPreparationService $jobPreparationService
     * @param CrawlJobContainerService $crawlJobContainerService
     * @param TaskOutputJoinerFactory $taskOutputJoinerFactory
     * @param TaskPostProcessorFactory $taskPostProcessorFactory
     * @param StateService $stateService
     * @param TaskTypeDomainsToIgnoreService $taskTypeDomainsToIgnoreService
     *
     * @return Response
     */
    public function completeAction(
        ApplicationStateService $applicationStateService,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        CompleteRequestFactory $completeRequestFactory,
        TaskService $taskService,
        JobService $jobService,
        JobPreparationService $jobPreparationService,
        CrawlJobContainerService $crawlJobContainerService,
        TaskOutputJoinerFactory $taskOutputJoinerFactory,
        TaskPostProcessorFactory $taskPostProcessorFactory,
        StateService $stateService,
        TaskTypeDomainsToIgnoreService $taskTypeDomainsToIgnoreService
    ) {
        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $completeRequest = $completeRequestFactory->create();
        if (!$completeRequest->isValid()) {
            throw new BadRequestHttpException();
        }

        $tasks = $completeRequest->getTasks();
        if (empty($tasks)) {
            throw new GoneHttpException();
        }

        $endDateTime = $completeRequest->getEndDateTime();

        $output = new Output();
        $output->setOutput($completeRequest->getOutput());
        $output->setContentType($completeRequest->getContentType());
        $output->setErrorCount($completeRequest->getErrorCount());
        $output->setWarningCount($completeRequest->getWarningCount());

        $state = $completeRequest->getState();

        $urlDiscoveryTaskType = $this->taskTypeService->getUrlDiscoveryTaskType();

        foreach ($tasks as $task) {
            $currentTaskOutput = $task->getOutput();

            if (!empty($currentTaskOutput)) {
                $taskOutputJoiner = $taskOutputJoinerFactory->getPreprocessor($task->getType());

                if (!empty($taskOutputJoiner)) {
                    $output = $taskOutputJoiner->join(array(
                        $task->getOutput(),
                        $output
                    ));
                }
            }

            $taskService->complete($task, $endDateTime, $output, $state);

            $taskPostProcessor = $taskPostProcessorFactory->getPostProcessor($task->getType());
            if (!empty($taskPostProcessor)) {
                $taskPostProcessor->process($task);
            }

            if (!$jobService->hasIncompleteTasks($task->getJob())) {
                $jobService->complete($task->getJob());
            }

            if ($task->getType()->equals($urlDiscoveryTaskType)) {
                if (JobService::COMPLETED_STATE === $task->getJob()->getState()->getName()) {
                    $jobFailedNoSitemapState = $stateService->get(JobService::FAILED_NO_SITEMAP_STATE);

                    /* @var CrawlJobContainerRepository $crawlJobContainerRepository */
                    $crawlJobContainerRepository = $this->entityManager->getRepository(CrawlJobContainer::class);

                    if ($crawlJobContainerRepository->doesCrawlTaskParentJobStateMatchState(
                        $task,
                        $jobFailedNoSitemapState
                    )) {
                        $crawlJobContainer = $crawlJobContainerService->getForJob($task->getJob());

                        foreach ($crawlJobContainer->getParentJob()->getRequestedTaskTypes() as $taskType) {
                            $taskTypeDomainsToIgnore = $taskTypeDomainsToIgnoreService->getForTaskType($taskType);

                            if (!empty($taskTypeDomainsToIgnore)) {
                                $jobPreparationService->setPredefinedDomainsToIgnore(
                                    $taskType,
                                    $taskTypeDomainsToIgnore
                                );
                            }
                        }

                        $jobPreparationService->prepareFromCrawl($crawlJobContainer);
                    }
                }

                $resqueQueueService->enqueue(
                    $resqueJobFactory->create(
                        'tasks-notify'
                    )
                );
            }
        }

        return new Response();
    }

    /**
     * @param string $task_type
     * @param string $state_name
     *
     * @return JsonResponse
     */
    public function taskTypeCountAction($task_type, $state_name)
    {
        $taskType = $this->taskTypeService->get($task_type);

        if (empty($taskType)) {
            throw new NotFoundHttpException();
        }

        $stateRepository = $this->entityManager->getRepository(State::class);

        /* @var State $state */
        $state = $stateRepository->findOneBy([
            'name' => 'task-' . $state_name,
        ]);

        if (empty($state)) {
            throw new NotFoundHttpException();
        }

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $count = $taskRepository->getCountByTaskTypeAndState($taskType, $state);

        return new JsonResponse($count);
    }
}
