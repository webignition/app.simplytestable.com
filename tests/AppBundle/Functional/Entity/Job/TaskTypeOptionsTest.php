<?php

namespace Tests\AppBundle\Functional\Entity\Job;

use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\Job\TaskTypeOptions;

class TaskTypeOptionsTest extends AbstractBaseTestCase
{
    public function testUtf8Options()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $taskTypeOptionsRepository = $entityManager->getRepository(TaskTypeOptions::class);

        $jobFactory = new JobFactory(self::$container);

        $job = $jobFactory->create();

        $taskType = $taskTypeService->getHtmlValidationTaskType();

        $optionsValue = 'ɸ';

        $options = new TaskTypeOptions();
        $options->setJob($job);
        $options->setTaskType($taskType);
        $options->setOptions($optionsValue);

        $entityManager->persist($options);
        $entityManager->flush();

        $optionsId = $options->getId();

        $entityManager->clear();

        $this->assertEquals(
            $optionsValue,
            $taskTypeOptionsRepository->find($optionsId)->getOptions()
        );
    }
}