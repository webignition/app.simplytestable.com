<?php

namespace App\Tests\Services;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use Doctrine\ORM\EntityManagerInterface;

class TaskOutputFactory
{
    const KEY_OUTPUT = 'output';
    const KEY_ERROR_COUNT = 'error-count';
    const KEY_WARNING_COUNT = 'warning-count';
    const KEY_HASH = 'hash';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Task $task
     * @param array $taskOutputValues
     *
     * @return Output
     */
    public function create(Task $task, $taskOutputValues)
    {
        $output = new Output();

        if (isset($taskOutputValues[self::KEY_OUTPUT])) {
            $output->setOutput($taskOutputValues[self::KEY_OUTPUT]);
        }

        if (isset($taskOutputValues[self::KEY_ERROR_COUNT])) {
            $output->setErrorCount($taskOutputValues[self::KEY_ERROR_COUNT]);
        }

        if (isset($taskOutputValues[self::KEY_WARNING_COUNT])) {
            $output->setWarningCount($taskOutputValues[self::KEY_WARNING_COUNT]);
        }

        if (array_key_exists(self::KEY_HASH, $taskOutputValues)) {
            $output->setHash($taskOutputValues[self::KEY_HASH]);
        } else {
            $output->generateHash();
        }

        $task->setOutput($output);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $output;
    }
}
