<?php

namespace Tests\ApiBundle\Functional\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
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

        $this->factory = $this->container->get(Factory::class);
    }

    public function testGetLinkIntegrityTaskPreProcessor()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
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
