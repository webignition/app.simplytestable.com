<?php

namespace Tests\ApiBundle\Functional\Services\TaskPostProcessor;

use SimplyTestable\ApiBundle\Services\TaskPostProcessor\UrlDiscoveryTaskPostProcessor;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory;
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

        $this->factory = $this->container->get('simplytestable.services.taskpostprocessor.factory');
    }

    public function testGetUrlDiscoveryTaskPostProcessor()
    {
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $this->assertInstanceOf(
            UrlDiscoveryTaskPostProcessor::class,
            $this->factory->getPostProcessor($taskTypeService->getUrlDiscoveryTaskType())
        );
    }
}
