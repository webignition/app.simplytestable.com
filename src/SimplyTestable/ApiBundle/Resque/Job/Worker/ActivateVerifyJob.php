<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Worker;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Command\Worker\ActivateVerifyCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;

class ActivateVerifyJob extends CommandJob
{
    const QUEUE_NAME = 'worker-activate-verify';

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

        /* @var WorkerActivationRequestService $workerActivationRequestService */
        $workerActivationRequestService = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var EntityRepository $workerRepository */
        $workerRepository = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var EntityRepository $workerRepository */
        $workerActivationRequestRepository = $this->getContainer()->get($this->args['serviceIds'][3]);

        return new ActivateVerifyCommand(
            $applicationStateService,
            $workerActivationRequestService,
            $workerRepository,
            $workerActivationRequestRepository
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        return [
            'id' => $this->args['id']
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return $this->args['id'];
    }
}
