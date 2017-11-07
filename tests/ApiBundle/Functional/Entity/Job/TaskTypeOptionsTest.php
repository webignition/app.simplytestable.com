<?php

namespace Tests\ApiBundle\Functional\Entity\Job;

use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;

class TaskTypeOptionsTest extends AbstractBaseTestCase
{
    public function testUtf8Options()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $taskTypeOptionsRepository = $this->container->get('simplytestable.repository.tasktypeoptions');

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

        $this->assertEquals(
            $optionsValue,
            $taskTypeOptionsRepository->find($optionsId)->getOptions()
        );
    }
}
