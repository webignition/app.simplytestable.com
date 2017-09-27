<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskOutputFactory
{
    const KEY_OUTPUT = 'output';
    const KEY_ERROR_COUNT = 'error-count';
    const KEY_WARNING_COUNT = 'warning-count';
    const KEY_HASH = 'hash';

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
     * @param array $taskOutputValues
     *
     * @return Output
     */
    public function create(Task $task, $taskOutputValues)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

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

        $entityManager->persist($task);
        $entityManager->flush($task);

        return $output;
    }
}
