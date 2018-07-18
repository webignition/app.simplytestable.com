<?php

namespace App\Resque\Job\Stripe;

use App\Command\Stripe\Event\ProcessCommand;
use App\Resque\Job\CommandJob;

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
    public function getCommandName()
    {
        return ProcessCommand::NAME;
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
