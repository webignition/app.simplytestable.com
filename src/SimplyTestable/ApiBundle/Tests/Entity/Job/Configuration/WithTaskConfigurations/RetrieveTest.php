<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job\Configuration\WithTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;

class RetrieveTest extends WithTaskConfigurationsTest {

    /**
     * @var Configuration
     */
    private $originalConfiguration;

    /**
     * @var Configuration
     */
    private $retrievedConfiguration;

    /**
     * @var int
     */
    private $configurationId;

    public function setUp() {
        parent::setUp();

        $this->originalConfiguration = new Configuration();
        $this->originalConfiguration->setLabel('foo');
        $this->originalConfiguration->setUser($this->getUserService()->getPublicUser());
        $this->originalConfiguration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $this->originalConfiguration->setType(
            $this->getJobTypeService()->getFullSiteType()
        );
        $this->originalConfiguration->setParameters('bar');

        $this->getManager()->persist($this->originalConfiguration);
        $this->getManager()->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($this->originalConfiguration);
        $taskConfiguration->setType(
            $this->getTaskTypeService()->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $this->getManager()->persist($taskConfiguration);
        $this->getManager()->flush();

        $this->originalConfiguration->addTaskConfiguration($taskConfiguration);

        $this->getManager()->persist($this->originalConfiguration);
        $this->getManager()->flush();

        $this->configurationId = $this->originalConfiguration->getId();

        $this->getManager()->clear();

        $this->retrievedConfiguration = $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Configuration')->find($this->configurationId);
    }


    public function testOriginalAndRetrievedAreNotTheExactSameObject() {
        $this->assertNotEquals(
            spl_object_hash($this->originalConfiguration),
            spl_object_hash($this->retrievedConfiguration)
        );
    }

    public function testOriginalAndRetrievedAreTheSameEntity() {
        $this->assertEquals($this->originalConfiguration->getId(), $this->retrievedConfiguration->getId());
    }

    public function testRetrievedHasTaskConfigurations() {
        $this->assertEquals(1, count($this->retrievedConfiguration->getTaskConfigurations()));
    }
}
