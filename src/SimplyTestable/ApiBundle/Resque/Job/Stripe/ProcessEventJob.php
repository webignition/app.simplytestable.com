<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Stripe;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessEventJob extends CommandJob
{
    const QUEUE_NAME = 'stripe-event';

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

        /* @var LoggerInterface $logger */
        $logger = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getContainer()->get($this->args['serviceIds'][3]);

        return new ProcessCommand(
            $applicationStateService,
            $entityManager,
            $logger,
            $eventDispatcher
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        return [
            'stripeId' => $this->args['stripeId']
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return $this->args['stripeId'];
    }
}
