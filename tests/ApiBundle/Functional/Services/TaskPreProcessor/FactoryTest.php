<?php

namespace Tests\ApiBundle\Functional\Services\TaskPreProcessor;

use phpmock\functions\FixedValueFunction;
use phpmock\MockBuilder;
use phpmock\mockery\PHPMockery;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\TaskPreProcessor\Factory;
use SimplyTestable\ApiBundle\Services\TaskPreProcessor\LinkIntegrityTaskPreProcessor;
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

        $this->factory = $this->container->get('simplytestable.services.taskpreprocessor.factory');
    }

    public function testGetLinkIntegrityTaskPreProcessor()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeRepository = $entityManager->getRepository(Type::class);

        /* @var Type $linkIntegrityTaskType */
        $linkIntegrityTaskType = $taskTypeRepository->findOneBy([
            'name' => TaskTypeService::LINK_INTEGRITY_TYPE,
        ]);

        $this->assertInstanceOf(LinkIntegrityTaskPreProcessor::class, $this->factory->getPreprocessor($linkIntegrityTaskType));
    }
}
