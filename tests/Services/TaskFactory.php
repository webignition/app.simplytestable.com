<?php

namespace App\Tests\Services;

use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Services\StateService;
use Doctrine\ORM\EntityManagerInterface;

class TaskFactory
{
    const KEY_STATE = 'state';
    const KEY_TYPE = 'type';
    const KEY_OUTPUT = 'output';
    const KEY_PARAMETERS = 'parameters';
    const KEY_TIME_PERIOD = 'time-period';
    const KEY_URL = 'url';

    private $entityManager;
    private $stateService;

    public function __construct(
        EntityManagerInterface $entityManager,
        StateService $stateService
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
    }

    /**
     * @param Task $task
     * @param array $taskValues
     */
    public function update(Task $task, $taskValues)
    {
        $isUpdated = false;

        $state = $taskValues[self::KEY_STATE] ?? null;
        if (is_string($state)) {
            $state = $this->stateService->get($state);
        }

        if ($state) {
            $task->setState($state);
            $isUpdated = true;
        }

        if (isset($taskValues[self::KEY_TYPE])) {
            $task->setType($taskValues[self::KEY_TYPE]);
            $isUpdated = true;
        }

        if (isset($taskValues[self::KEY_OUTPUT])) {
            $task->setOutput($taskValues[self::KEY_OUTPUT]);
            $isUpdated = true;
        }

        if (isset($taskValues[self::KEY_PARAMETERS])) {
            $task->setParameters($taskValues[self::KEY_PARAMETERS]);
            $isUpdated = true;
        }

        if (isset($taskValues[self::KEY_TIME_PERIOD])) {
            $task->setTimePeriod($taskValues[self::KEY_TIME_PERIOD]);
            $isUpdated = true;
        }

        if (isset($taskValues[self::KEY_URL])) {
            $task->setUrl($taskValues[self::KEY_URL]);
            $isUpdated = true;
        }

        if ($isUpdated) {
            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Task $task
     * @param \DateTime $endDateTime
     */
    public function setEndDateTime(Task $task, \DateTime $endDateTime)
    {
        $timePeriod = $task->getTimePeriod();

        if (empty($timePeriod)) {
            $timePeriod = new TimePeriod();

            $startDateTime = clone $endDateTime;
            $startDateTime->modify('-1 hour');

            $timePeriod->setStartDateTime($startDateTime);

            $this->entityManager->persist($timePeriod);
            $this->entityManager->flush();

            $task->setTimePeriod($timePeriod);
        }

        $timePeriod->setEndDateTime($endDateTime);
        $this->entityManager->persist($timePeriod);
        $this->entityManager->flush();

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
}
