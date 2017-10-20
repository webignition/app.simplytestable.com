<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\FactoryService;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class TaskController extends ApiController
{
    /**
     * @return Response
     */
    public function completeAction()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $completeRequest = $this->container->get('simplytestable.services.request.factory.task.complete')->create();
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

        $urlDiscoveryTaskType = $this->getTaskTypeService()->getByName('URL discovery');

        $crawlJobContainerService = $this->getCrawlJobContainerService();

        /* @var CrawlJobContainerRepository $crawlJobContainerRepository */
        $crawlJobContainerRepository = $entityManager->getRepository(CrawlJobContainer::class);

        foreach ($tasks as $task) {
            if ($task->hasOutput() && $this->getTaskOutputJoinerFactoryService()->hasTaskOutputJoiner($task)) {
                $output = $this->getTaskOutputJoinerFactoryService()->getTaskOutputJoiner($task)->join(array(
                    $task->getOutput(),
                    $output
                ));
            }

            $this->getTaskService()->complete($task, $endDateTime, $output, $state, false);

            if ($task->getType()->equals($urlDiscoveryTaskType)) {
                $crawlJobContainerService->processTaskResults($task);
            }

            if (!$this->getJobService()->hasIncompleteTasks($task->getJob())) {
                $this->getJobService()->complete($task->getJob());
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
                                $this->getJobPreparationService()->setPredefinedDomainsToIgnore(
                                    $taskType,
                                    $this->container->getParameter($taskTypeParameterDomainsToIgnoreKey)
                                );
                            }
                        }

                        $this->getJobPreparationService()->prepareFromCrawl($crawlJobContainer);
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
     * @return Response
     */
    public function taskTypeCountAction($task_type, $state_name)
    {
        $taskStatePrefix = 'task-';

        if (!$this->getTaskTypeService()->exists($task_type)) {
            return new Response('', 404);
        }

        if (!$this->getStateService()->has($taskStatePrefix . $state_name)) {
            return new Response('', 404);
        }

        $taskType = $this->getTaskTypeService()->getByName($task_type);
        $state = $this->getStateService()->fetch($taskStatePrefix . $state_name);

        return new Response(json_encode($this->getTaskService()->getCountByTaskTypeAndState($taskType, $state)), 200);
    }

    /**
     * @return TaskService
     */
    private function getTaskService()
    {
        return $this->container->get('simplytestable.services.taskservice');
    }

    /**
     * @return JobService
     */
    private function getJobService()
    {
        return $this->container->get('simplytestable.services.jobservice');
    }

    /**
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */
    private function getJobPreparationService()
    {
        return $this->container->get('simplytestable.services.jobpreparationservice');
    }

    /**
     * @return TaskTypeService
     */
    private function getTaskTypeService()
    {
        return $this->container->get('simplytestable.services.tasktypeservice');
    }

    /**
     * @return StateService
     */
    private function getStateService()
    {
        return $this->container->get('simplytestable.services.stateservice');
    }

    /**
     * @return CrawlJobContainerService
     */
    private function getCrawlJobContainerService()
    {
        return $this->container->get('simplytestable.services.crawljobcontainerservice');
    }

    /**
     * @return FactoryService
     */
    private function getTaskOutputJoinerFactoryService()
    {
        return $this->container->get('simplytestable.services.TaskOutputJoinerServiceFactory');
    }
}
