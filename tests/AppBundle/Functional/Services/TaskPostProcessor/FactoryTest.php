<?php

namespace Tests\AppBundle\Functional\Services\TaskPostProcessor;

use AppBundle\Services\TaskPostProcessor\UrlDiscoveryTaskPostProcessor;
use AppBundle\Services\TaskPostProcessor\Factory;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

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
