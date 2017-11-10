<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskController extends Controller
{
    /**
     * @return Response
     */
    public function completeAction()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $completeRequestFactory = $this->container->get('simplytestable.services.request.factory.task.complete');
        $taskTypeService = $this->container->get(TaskTypeService::class);
        $taskService = $this->container->get(TaskService::class);
        $jobService = $this->container->get(JobService::class);
        $jobPreparationService = $this->container->get('simplytestable.services.jobpreparationservice');
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $taskOutputJoinerFactory = $this->container->get('simplytestable.services.taskoutputjoiner.factory');
        $taskPostProcessorFactory = $this->container->get('simplytestable.services.taskpostprocessor.factory');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $stateService = $this->container->get(StateService::class);

        /* @var CrawlJobContainerRepository $crawlJobContainerRepository */
        $crawlJobContainerRepository = $entityManager->getRepository(CrawlJobContainer::class);

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

        $urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

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

                    if ($crawlJobContainerRepository->doesCrawlTaskParentJobStateMatchState(
                        $task,
                        $jobFailedNoSitemapState
                    )) {
                        $crawlJobContainer = $crawlJobContainerService->getForJob($task->getJob());

                        foreach ($crawlJobContainer->getParentJob()->getRequestedTaskTypes() as $taskType) {
                            /* @var $taskType TaskType */
                            $taskTypeParameterDomainsToIgnoreKey = strtolower(
                                str_replace(' ', '_', $taskType->getName())
                            ) . '_domains_to_ignore';

                            if ($this->container->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                                $jobPreparationService->setPredefinedDomainsToIgnore(
                                    $taskType,
                                    $this->container->getParameter($taskTypeParameterDomainsToIgnoreKey)
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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get(TaskTypeService::class);

        /* @var TaskRepository $taskRepository */
        $taskRepository = $entityManager->getRepository(Task::class);
        $stateRepository = $entityManager->getRepository(State::class);

        $taskType = $taskTypeService->get($task_type);

        if (empty($taskType)) {
            throw new NotFoundHttpException();
        }

        /* @var State $state */
        $state = $stateRepository->findOneBy([
            'name' => 'task-' . $state_name,
        ]);

        if (empty($state)) {
            throw new NotFoundHttpException();
        }

        $count = $taskRepository->getCountByTaskTypeAndState($taskType, $state);

        return new JsonResponse($count);
    }
}
