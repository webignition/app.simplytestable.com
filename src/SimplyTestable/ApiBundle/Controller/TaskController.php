<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskController extends ApiController
{
    /**
     * @return Response
     */
    public function completeAction()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $completeRequestFactory = $this->container->get('simplytestable.services.request.factory.task.complete');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $jobPreparationService = $this->container->get('simplytestable.services.jobpreparationservice');
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $taskOutputJoinerFactory = $this->container->get('simplytestable.services.taskoutputjoinerservicefactory');

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

        $urlDiscoveryTaskType = $taskTypeService->getByName('URL discovery');

        /* @var CrawlJobContainerRepository $crawlJobContainerRepository */
        $crawlJobContainerRepository = $entityManager->getRepository(CrawlJobContainer::class);

        foreach ($tasks as $task) {
            $currentTaskOutput = $task->getOutput();

            if (!empty($currentTaskOutput) && $taskOutputJoinerFactory->hasTaskOutputJoiner($task)) {
                $output = $taskOutputJoinerFactory->getTaskOutputJoiner($task)->join(array(
                    $task->getOutput(),
                    $output
                ));
            }

            $taskService->complete($task, $endDateTime, $output, $state, false);

            if ($task->getType()->equals($urlDiscoveryTaskType)) {
                $crawlJobContainerService->processTaskResults($task);
            }

            if (!$jobService->hasIncompleteTasks($task->getJob())) {
                $jobService->complete($task->getJob());
            }

            if ($task->getType()->equals($urlDiscoveryTaskType)) {
                $stateService = $this->container->get('simplytestable.services.stateservice');

                if (JobService::COMPLETED_STATE === $task->getJob()->getState()->getName()) {
                    $jobFailedNoSitemapState = $stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE);

                    if ($crawlJobContainerRepository->doesCrawlTaskParentJobStateMatchState(
                        $task,
                        $jobFailedNoSitemapState
                    )) {
                        $crawlJobContainer = $crawlJobContainerService->getForJob($task->getJob());

                        foreach ($crawlJobContainer->getParentJob()->getRequestedTaskTypes() as $taskType) {
                            /* @var $taskType TaskType */
                            $taskTypeParameterDomainsToIgnoreKey = strtolower(
                                str_replace(' ', '-', $taskType->getName())
                            ) . '-domains-to-ignore';

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

        $taskTypeRepository = $entityManager->getRepository(TaskType::class);

        /* @var TaskType $taskType */
        $taskType = $taskTypeRepository->findOneBy([
            'name' => $task_type,
        ]);

        if (empty($taskType)) {
            throw new NotFoundHttpException();
        }

        $stateRepository = $entityManager->getRepository(State::class);

        /* @var State $state */
        $state = $stateRepository->findOneBy([
            'name' => 'task-' . $state_name,
        ]);

        if (empty($state)) {
            throw new NotFoundHttpException();
        }

        $taskRepository = $entityManager->getRepository(Task::class);
        $count = $taskRepository->getCountByTaskTypeAndState($taskType, $state);

        return new JsonResponse($count);
    }
}
