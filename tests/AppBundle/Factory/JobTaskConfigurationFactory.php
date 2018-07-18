<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\Job\TaskConfiguration;
use AppBundle\Services\TaskTypeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobTaskConfigurationFactory
{
    const KEY_TYPE = 'type';
    const KEY_OPTIONS = 'options';

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
     * @param array $jobTaskConfigurationValues
     *
     * @return TaskConfiguration
     */
    public function create($jobTaskConfigurationValues)
    {
        $jobTaskConfiguration = new TaskConfiguration();

        $taskTypeService = $this->container->get(TaskTypeService::class);
        $taskType = $taskTypeService->get($jobTaskConfigurationValues[self::KEY_TYPE]);

        $jobTaskConfiguration->setType($taskType);

        if (isset($jobTaskConfigurationValues[self::KEY_OPTIONS])) {
            $jobTaskConfiguration->setOptions($jobTaskConfigurationValues[self::KEY_OPTIONS]);
        }

        return $jobTaskConfiguration;
    }
}
