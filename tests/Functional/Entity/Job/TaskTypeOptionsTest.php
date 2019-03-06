<?php

namespace App\Tests\Functional\Entity\Job;

use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Job\TaskTypeOptions;
use App\Tests\Services\JobFactory;

class TaskTypeOptionsTest extends AbstractBaseTestCase
{
    public function testUtf8Options()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $taskTypeOptionsRepository = $entityManager->getRepository(TaskTypeOptions::class);

        $jobFactory = self::$container->get(JobFactory::class);

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
