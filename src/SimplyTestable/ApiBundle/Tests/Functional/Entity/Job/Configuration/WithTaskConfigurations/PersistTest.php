<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job\Configuration\WithTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

class PersistTest extends WithTaskConfigurationsTest {

    /**
     * @var Configuration
     */
    private $configuration;

    public function setUp() {
        parent::setUp();

        $this->configuration = new Configuration();
        $this->configuration->setLabel('foo');
        $this->configuration->setUser($this->getUserService()->getPublicUser());
        $this->configuration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $this->configuration->setType(
            $this->getJobTypeService()->getFullSiteType()
        );
        $this->configuration->setParameters('bar');

        $this->getManager()->persist($this->configuration);
        $this->getManager()->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($this->configuration);
        $taskConfiguration->setType(
            $this->getTaskTypeService()->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $this->getManager()->persist($taskConfiguration);
        $this->getManager()->flush();

        $this->configuration->addTaskConfiguration($taskConfiguration);

        $this->getManager()->persist($this->configuration);
        $this->getManager()->flush();
    }


    public function testConfigurationIsPersisted() {
        $this->assertNotNull($this->configuration->getId());
    }

    public function testTaskConfigurationsExist() {
        $this->assertEquals(1, count($this->configuration->getTaskConfigurations()));
    }

    public function testTaskConfigurationsArePersisted() {
        /* @var $taskConfiguration TaskConfiguration */
        foreach ($this->configuration->getTaskConfigurations() as $taskConfiguration) {
            $this->assertNotNull($taskConfiguration->getId());
        }
    }
}
