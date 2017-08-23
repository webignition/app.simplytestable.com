<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Worker;

use SimplyTestable\ApiBundle\Command\WorkerActivateVerifyCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

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
        return new WorkerActivateVerifyCommand();
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
