<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskPreProcessor\FactoryService as TaskPreProcessorFactoryService;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;

class AssignCollectionJob extends CommandJob
{
    const QUEUE_NAME = 'task-assign-collection';

    /**
     * {@inheritdoc}
     */
    protected function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand()
    {
        /* @var ApplicationStateService $applicationStateService */
        $applicationStateService = $this->getContainer()->get($this->args['serviceIds'][0]);

        /* @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var TaskPreProcessorFactoryService $taskPreProcessorFactory */
        $taskPreProcessorFactory = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var WorkerService $workerService */
        $workerService = $this->getContainer()->get($this->args['serviceIds'][3]);

        /* @var ResqueQueueService $resqueQueueService */
        $resqueQueueService = $this->getContainer()->get($this->args['serviceIds'][4]);

        /* @var ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = $this->getContainer()->get($this->args['serviceIds'][5]);

        /* @var StateService $stateService */
        $stateService = $this->getContainer()->get($this->args['serviceIds'][6]);

        /* @var WorkerTaskAssignmentService $workerTaskAssignmentService */
        $workerTaskAssignmentService = $this->getContainer()->get($this->args['serviceIds'][7]);

        /* @var LoggerInterface $logger */
        $logger = $this->getContainer()->get($this->args['serviceIds'][8]);

        return new CollectionCommand(
            $applicationStateService,
            $entityManager,
            $taskPreProcessorFactory,
            $workerService,
            $resqueQueueService,
            $resqueJobFactory,
            $stateService,
            $workerTaskAssignmentService,
            $logger
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        if (isset($this->args['worker'])) {
            return [
                'ids' => $this->args['ids'],
                'worker' => $this->args['worker']
            ];
        }

        return [
            'ids' => $this->args['ids']
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return $this->args['ids'];
    }
}
