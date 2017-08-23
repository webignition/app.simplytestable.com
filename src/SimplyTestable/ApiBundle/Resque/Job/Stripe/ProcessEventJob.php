<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Stripe;

use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

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
        return new ProcessCommand();
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
