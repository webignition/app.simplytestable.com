<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job\Configuration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithTaskConfigurationsTest extends ConfigurationTest {

    public function testPersist() {
        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel('foo');
        $jobConfiguration->setUser($this->getUserService()->getPublicUser());
        $jobConfiguration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $jobConfiguration->setType(
            $this->getJobTypeService()->getFullSiteType()
        );
        $jobConfiguration->setParameters('bar');

        $this->getManager()->persist($jobConfiguration);
        $this->getManager()->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($jobConfiguration);
        $taskConfiguration->setType(
            $this->getTaskTypeService()->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $this->getManager()->persist($taskConfiguration);
        $this->getManager()->flush();

        $jobConfiguration->addTaskConfiguration($taskConfiguration);

        $this->getManager()->persist($jobConfiguration);
        $this->getManager()->flush();
    }
}
