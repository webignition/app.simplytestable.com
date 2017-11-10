<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Task;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Command\Task\Cancel\CollectionCommand;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService;

class CancelCollectionJob extends CommandJob
{
    const QUEUE_NAME = 'task-cancel-collection';

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

        /* @var TaskService $taskService */
        $taskService = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var WorkerTaskCancellationService $workerTaskCancellationService */
        $workerTaskCancellationService = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var LoggerInterface $logger */
        $logger = $this->getContainer()->get($this->args['serviceIds'][3]);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->getContainer()->get($this->args['serviceIds'][4]);

        return new CollectionCommand(
            $applicationStateService,
            $taskService,
            $workerTaskCancellationService,
            $logger,
            $entityManager
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
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
