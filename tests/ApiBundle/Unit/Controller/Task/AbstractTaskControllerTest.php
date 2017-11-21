<?php

namespace Tests\ApiBundle\Unit\Controller\Task;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\MockFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractTaskControllerTest extends AbstractBaseTestCase
{
    /**
     * @param array $services
     *
     * @return TaskController
     */
    protected function createTaskController($services = [])
    {
        if (!isset($services[EntityManagerInterface::class])) {
            $services[EntityManagerInterface::class] = MockFactory::createEntityManager();
        }

        if (!isset($services[TaskTypeService::class])) {
            $services[TaskTypeService::class] = MockFactory::createTaskTypeService();
        }

        $jobConfigurationController = new TaskController(
            $services[EntityManagerInterface::class],
            $services[TaskTypeService::class]
        );

        return $jobConfigurationController;
    }
}
