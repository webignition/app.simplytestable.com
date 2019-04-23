<?php

namespace App\Tests\Functional\Services\TaskPreProcessor;

use App\Entity\Task\TaskType;
use App\Services\TaskPreProcessor\Factory;
use App\Services\TaskPreProcessor\LinkIntegrityTaskPreProcessor;
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

    public function testGetLinkIntegrityTaskPreProcessor()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeRepository = $entityManager->getRepository(TaskType::class);

        /* @var TaskType $linkIntegrityTaskType */
        $linkIntegrityTaskType = $taskTypeRepository->findOneBy([
            'name' => TaskTypeService::LINK_INTEGRITY_TYPE,
        ]);

        $this->assertInstanceOf(
            LinkIntegrityTaskPreProcessor::class,
            $this->factory->getPreprocessor($linkIntegrityTaskType)
        );
    }
}
