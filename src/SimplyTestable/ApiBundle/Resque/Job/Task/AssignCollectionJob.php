<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskPreProcessor\Factory as TaskPreProcessorFactory;
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

        /* @var TaskPreProcessorFactory $taskPreProcessorFactory */
        $taskPreProcessorFactory = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var ResqueQueueService $resqueQueueService */
        $resqueQueueService = $this->getContainer()->get($this->args['serviceIds'][3]);

        /* @var ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = $this->getContainer()->get($this->args['serviceIds'][4]);

        /* @var StateService $stateService */
        $stateService = $this->getContainer()->get($this->args['serviceIds'][5]);

        /* @var WorkerTaskAssignmentService $workerTaskAssignmentService */
        $workerTaskAssignmentService = $this->getContainer()->get($this->args['serviceIds'][6]);

        /* @var LoggerInterface $logger */
        $logger = $this->getContainer()->get($this->args['serviceIds'][7]);

        /* @var TaskRepository $taskRepository */
        $taskRepository = $this->getContainer()->get($this->args['serviceIds'][8]);

        /* @var EntityRepository $taskRepository */
        $workerRepository = $this->getContainer()->get($this->args['serviceIds'][9]);

        return new CollectionCommand(
            $applicationStateService,
            $entityManager,
            $taskPreProcessorFactory,
            $resqueQueueService,
            $resqueJobFactory,
            $stateService,
            $workerTaskAssignmentService,
            $logger,
            $taskRepository,
            $workerRepository
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
