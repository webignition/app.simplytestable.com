<?php

namespace Tests\ApiBundle\Functional\Services\TaskPostProcessor;

use SimplyTestable\ApiBundle\Services\TaskPostProcessor\UrlDiscoveryTaskPostProcessor;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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

        $this->factory = $this->container->get(Factory::class);
    }

    public function testGetUrlDiscoveryTaskPostProcessor()
    {
        $taskTypeService = $this->container->get(TaskTypeService::class);

        $this->assertInstanceOf(
            UrlDiscoveryTaskPostProcessor::class,
            $this->factory->getPostProcessor($taskTypeService->getUrlDiscoveryTaskType())
        );
    }
}
