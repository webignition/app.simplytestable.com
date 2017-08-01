<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class PersistTest extends TaskConfigurationTest {

    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration;

    protected function setUp() {
        parent::setUp();

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

        $taskConfigurationId = $taskConfiguration->getId();

        $this->getManager()->clear();

        $this->taskConfiguration = $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration')->find($taskConfigurationId);
    }

    public function testIsPersisted() {
        $this->assertNotNull($this->taskConfiguration->getId());
    }

    public function testDefaultIsEnabled() {
        $this->assertTrue($this->taskConfiguration->getIsEnabled());
    }

}
