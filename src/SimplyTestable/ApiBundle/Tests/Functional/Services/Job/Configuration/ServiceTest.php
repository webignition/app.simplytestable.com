<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration;

use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

abstract class ServiceTest extends AbstractBaseTestCase {

    /**
     * @return \SimplyTestable\ApiBundle\Services\Job\ConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->container->get('simplytestable.services.job.configurationservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\ScheduledJob\Service
     */
    protected function getScheduledJobService() {
        return $this->container->get('simplytestable.services.scheduledjob.service');
    }


    /**
     * @return TaskConfigurationCollection
     */
    protected function getStandardTaskConfigurationCollection()
    {
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setType(
            $taskTypeService->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $taskConfigurationCollection = new TaskConfigurationCollection();
        $taskConfigurationCollection->add($taskConfiguration);

        return $taskConfigurationCollection;
    }


    /**
     * @param $taskConfigurationDetails
     * @return TaskConfigurationCollection
     */
    protected function getTaskConfigurationCollection($taskConfigurationDetails)
    {
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($taskConfigurationDetails as $taskName => $options) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $taskTypeService->getByName($taskName)
            );

            $taskConfiguration->setOptions($options);
            $taskConfigurationCollection->add($taskConfiguration);
        }

        return $taskConfigurationCollection;
    }

}
