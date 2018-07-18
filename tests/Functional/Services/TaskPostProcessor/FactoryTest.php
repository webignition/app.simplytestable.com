<?php

namespace App\Tests\Functional\Services\TaskPostProcessor;

use App\Services\TaskPostProcessor\UrlDiscoveryTaskPostProcessor;
use App\Services\TaskPostProcessor\Factory;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;

class FactoryTest extends AbstractBaseTestCase
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->factory = self::$container->get(Factory::class);
    }

    public function testGetUrlDiscoveryTaskPostProcessor()
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $this->assertInstanceOf(
            UrlDiscoveryTaskPostProcessor::class,
            $this->factory->getPostProcessor($taskTypeService->getUrlDiscoveryTaskType())
        );
    }
}
