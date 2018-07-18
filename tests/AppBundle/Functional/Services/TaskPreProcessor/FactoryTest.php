<?php

namespace Tests\AppBundle\Functional\Services\TaskPreProcessor;

use AppBundle\Entity\Task\Type\Type;
use AppBundle\Services\TaskPreProcessor\Factory;
use AppBundle\Services\TaskPreProcessor\LinkIntegrityTaskPreProcessor;
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

    public function testGetLinkIntegrityTaskPreProcessor()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeRepository = $entityManager->getRepository(Type::class);

        /* @var Type $linkIntegrityTaskType */
        $linkIntegrityTaskType = $taskTypeRepository->findOneBy([
            'name' => TaskTypeService::LINK_INTEGRITY_TYPE,
        ]);

        $this->assertInstanceOf(
            LinkIntegrityTaskPreProcessor::class,
            $this->factory->getPreprocessor($linkIntegrityTaskType)
        );
    }
}
