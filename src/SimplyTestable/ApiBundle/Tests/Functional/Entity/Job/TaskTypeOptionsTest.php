<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;

class TaskTypeOptionsTest extends BaseSimplyTestableTestCase
{
    public function testUtf8Options()
    {
        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create();

        $taskType = $this->getTaskTypeService()->getByName('HTML Validation');

        $optionsValue = 'ɸ';

        $options = new TaskTypeOptions();
        $options->setJob($job);
        $options->setTaskType($taskType);
        $options->setOptions($optionsValue);

        $this->getManager()->persist($options);
        $this->getManager()->flush();

        $optionsId = $options->getId();

        $this->getManager()->clear();

        $this->assertEquals(
            $optionsValue,
            $this
                ->getManager()
                ->getRepository('SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions')
                ->find($optionsId)
                ->getOptions()
        );
    }
}
