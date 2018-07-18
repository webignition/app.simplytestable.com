<?php

namespace App\Resque\Job\Worker;

use App\Command\Worker\ActivateVerifyCommand;
use App\Resque\Job\CommandJob;

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
    public function getCommandName()
    {
        return ActivateVerifyCommand::NAME;
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
