<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Create;

use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;

class WithEmptyTaskConfigurationCollectionTest extends ServiceTest {

    const LABEL = 'foo';


    public function testEmptyCollectionThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception',
            'TaskConfigurationCollection is empty',
            JobConfigurationServiceException::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY
        );

        $this->getJobConfigurationService()->setUser($this->getUserService()->getPublicUser());
        $this->getJobConfigurationService()->create(
            $this->getWebSiteService()->fetch('http://example.com/'),
            $this->getJobTypeService()->getFullSiteType(),
            new TaskConfigurationCollection(),
            self::LABEL,
            ''
        );
    }

}