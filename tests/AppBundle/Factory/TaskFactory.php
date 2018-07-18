<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\Task\Task;
use AppBundle\Entity\TimePeriod;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskFactory
{
    const KEY_STATE = 'state';
    const KEY_TYPE = 'type';
    const KEY_OUTPUT = 'output';
    const KEY_PARAMETERS = 'parameters';
    const KEY_TIME_PERIOD = 'time-period';
    const KEY_WORKER = 'worker';
    const KEY_URL = 'url';
    const KEY_REMOTE_ID = 'remote-id';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Task $task
     * @param array $taskValues
     */
    public function update(Task $task, $taskValues)
    {
        if (isset($taskValues[self::KEY_STATE])) {
            $task->setState($taskValues[self::KEY_STATE]);
        }

        if (isset($taskValues[self::KEY_TYPE])) {
            $task->setType($taskValues[self::KEY_TYPE]);
        }

        if (isset($taskValues[self::KEY_OUTPUT])) {
            $task->setOutput($taskValues[self::KEY_OUTPUT]);
        }

        if (isset($taskValues[self::KEY_PARAMETERS])) {
            $task->setParameters($taskValues[self::KEY_PARAMETERS]);
        }

        if (isset($taskValues[self::KEY_TIME_PERIOD])) {
            $task->setTimePeriod($taskValues[self::KEY_TIME_PERIOD]);
        }

        if (isset($taskValues[self::KEY_REMOTE_ID])) {
            $task->setRemoteId($taskValues[self::KEY_REMOTE_ID]);
        }

        if (isset($taskValues[self::KEY_WORKER])) {
            $task->setWorker($taskValues[self::KEY_WORKER]);
        }

        if (isset($taskValues[self::KEY_URL])) {
            $task->setUrl($taskValues[self::KEY_URL]);
        }

        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $entityManager->persist($task);
        $entityManager->flush();
    }

    /**
     * @param Task $task
     * @param \DateTime $endDateTime
     */
    public function setEndDateTime(Task $task, \DateTime $endDateTime)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $timePeriod = $task->getTimePeriod();

        if (empty($timePeriod)) {
            $timePeriod = new TimePeriod();

            $startDateTime = clone $endDateTime;
            $startDateTime->modify('-1 hour');

            $timePeriod->setStartDateTime($startDateTime);

            $entityManager->persist($timePeriod);
            $entityManager->flush($timePeriod);

            $task->setTimePeriod($timePeriod);
        }

        $timePeriod->setEndDateTime($endDateTime);
        $entityManager->persist($timePeriod);
        $entityManager->flush($timePeriod);

        $entityManager->persist($task);
        $entityManager->flush($task);
    }
}
