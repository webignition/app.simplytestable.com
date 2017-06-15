<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactoryService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
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
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

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
                if ($this->getJobService()->isCompleted($task->getJob())) {
                    $failedNoSitemapState = $this->getJobService()->getFailedNoSitemapState();

                    if ($crawlJobContainerService->getEntityRepository()->doesCrawlTaskParentStateMatchState(
                        $task,
                        $failedNoSitemapState
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

                $this->getResqueQueueService()->enqueue(
                    $this->getResqueJobFactoryService()->create(
                        'tasks-notify'
                    )
                );
            }
        }

        return $this->sendSuccessResponse();
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
        $state = $this->getStateService()->find($taskStatePrefix . $state_name);

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
     * @return QueueService
     */
    private function getResqueQueueService()
    {
        return $this->get('simplytestable.services.resque.queueService');
    }

    /**
     * @return JobFactoryService
     */
    private function getResqueJobFactoryService()
    {
        return $this->get('simplytestable.services.resque.jobFactoryService');
    }

    /**
     * @return FactoryService
     */
    private function getTaskOutputJoinerFactoryService()
    {
        return $this->container->get('simplytestable.services.TaskOutputJoinerServiceFactory');
    }
}
