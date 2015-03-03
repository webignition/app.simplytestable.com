<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job\Configuration\WithoutTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;

class PersistTest extends WithoutTaskConfigurationsTest {

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
    }


    public function testIsPersisted() {
        $this->assertNotNull($this->configuration->getId());
    }
}
