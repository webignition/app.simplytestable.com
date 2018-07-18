<?php

namespace App\Resque\Job\Task;

use App\Command\Task\Assign\CollectionCommand;
use App\Resque\Job\CommandJob;

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
    public function getCommandName()
    {
        return CollectionCommand::NAME;
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
