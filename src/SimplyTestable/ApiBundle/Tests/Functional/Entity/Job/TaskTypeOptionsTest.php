<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;

class TaskTypeOptionsTest extends AbstractBaseTestCase
{
    public function testUtf8Options()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create();

        $taskType = $taskTypeService->getByName('HTML Validation');

        $optionsValue = 'ɸ';

        $options = new TaskTypeOptions();
        $options->setJob($job);
        $options->setTaskType($taskType);
        $options->setOptions($optionsValue);

        $entityManager->persist($options);
        $entityManager->flush();

        $optionsId = $options->getId();

        $entityManager->clear();

        $taskTypeOptionsRepository = $entityManager->getRepository(TaskTypeOptions::class);

        $this->assertEquals(
            $optionsValue,
            $taskTypeOptionsRepository->find($optionsId)->getOptions()
        );
    }
}
