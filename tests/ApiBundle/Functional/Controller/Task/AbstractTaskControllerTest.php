<?php

namespace Tests\ApiBundle\Functional\Controller\Task;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeDomainsToIgnoreService;
use Symfony\Component\HttpFoundation\Response;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;

abstract class AbstractTaskControllerTest extends AbstractBaseTestCase
{
    /**
     * @var TaskController
     */
    protected $taskController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskController = $this->container->get(TaskController::class);
    }

    /**
     * @return Response
     */
    protected function callCompleteAction()
    {
        return $this->taskController->completeAction(
            $this->container->get(ApplicationStateService::class),
            $this->container->get(ResqueQueueService::class),
            $this->container->get(ResqueJobFactory::class),
            $this->container->get(CompleteRequestFactory::class),
            $this->container->get(TaskService::class),
            $this->container->get(JobService::class),
            $this->container->get(JobPreparationService::class),
            $this->container->get(CrawlJobContainerService::class),
            $this->container->get(TaskOutputJoinerFactory::class),
            $this->container->get(TaskPostProcessorFactory::class),
            $this->container->get(StateService::class),
            $this->container->get(TaskTypeDomainsToIgnoreService::class)
        );
    }
}
