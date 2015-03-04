<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\ServiceTest as BaseServiceTest;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

abstract class ServiceTest extends BaseServiceTest {


    /**
     * @return TaskConfigurationCollection
     */
    protected function getStandardTaskConfigurationCollection() {
        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setType(
            $this->getTaskTypeService()->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $taskConfigurationCollection = new TaskConfigurationCollection();
        $taskConfigurationCollection->add($taskConfiguration);

        return $taskConfigurationCollection;
    }

}
