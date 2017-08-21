<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskFactory
{
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
