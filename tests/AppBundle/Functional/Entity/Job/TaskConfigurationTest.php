<?php

namespace Tests\AppBundle\Functional\Entity\Job;

use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Job\TaskConfiguration;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobConfigurationFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

class TaskConfigurationTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
    }

    /**
     * @dataProvider persistDataProvider
     *
     * @param string $taskTypeName
     * @param array $options
     * @param bool $isEnabled
     * @param array $expectedOptions
     * @param bool $expectedIsEnabled
     */
    public function testPersist($taskTypeName, $options, $isEnabled, $expectedOptions, $expectedIsEnabled)
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $jobConfigurationFactory = new JobConfigurationFactory(self::$container);
        $taskConfigurationRepository = $this->entityManager->getRepository(TaskConfiguration::class);

        $jobConfiguration = $jobConfigurationFactory->create();
        $taskType = $taskTypeService->get($taskTypeName);

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setType($taskType);
        $taskConfiguration->setJobConfiguration($jobConfiguration);

        if (!is_null($options)) {
            $taskConfiguration->setOptions($options);
        }

        if (!is_null($isEnabled)) {
            $taskConfiguration->setIsEnabled($isEnabled);
        }

        $this->assertNull($taskConfiguration->getId());

        $this->entityManager->persist($taskConfiguration);
        $this->entityManager->flush();

        $this->assertNotNull($taskConfiguration->getId());

        $taskConfigurationId = $taskConfiguration->getId();

        $this->entityManager->clear();

        /* @var TaskConfiguration $retrievedTaskConfiguration */
        $retrievedTaskConfiguration = $taskConfigurationRepository->find($taskConfigurationId);

        $this->assertEquals($taskConfiguration->getId(), $retrievedTaskConfiguration->getId());
        $this->assertEquals($taskType->getName(), $retrievedTaskConfiguration->getType()->getName());
        $this->assertEquals($expectedOptions, $retrievedTaskConfiguration->getOptions());
        $this->assertEquals($expectedIsEnabled, $retrievedTaskConfiguration->getIsEnabled());
    }

    /**
     * @return array
     */
    public function persistDataProvider()
    {
        return [
            'html validation, null options, isEnabled=null' => [
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'options' => null,
                'isEnabled' => null,
                'expectedOptions' => [],
                'expectedIsEnabled' => true,
            ],
            'css validation, null options, isEnabled=null' => [
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'options' => null,
                'isEnabled' => null,
                'expectedOptions' => [],
                'expectedIsEnabled' => true,
            ],
            'html validation, non-empty options, isEnabled=false' => [
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'options' => [
                    'foo' => 'bar',
                ],
                'isEnabled' => false,
                'expectedOptions' => [
                    'foo' => 'bar',
                ],
                'expectedIsEnabled' => false,
            ],
        ];
    }
}
