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

        $this->factory = $this->container->get('simplytestable.services.taskpreprocessor.factory');
    }

    public function testGetLinkIntegrityTaskPreProcessor()
    {
        $taskTypeRepository = $this->container->get('simplytestable.repository.tasktype');

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
