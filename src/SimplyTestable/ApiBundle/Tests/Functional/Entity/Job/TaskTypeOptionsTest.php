<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;

class TaskTypeOptionsTest extends BaseSimplyTestableTestCase
{
    public function testUtf8Options()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create();

        $taskType = $this->getTaskTypeService()->getByName('HTML Validation');

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
