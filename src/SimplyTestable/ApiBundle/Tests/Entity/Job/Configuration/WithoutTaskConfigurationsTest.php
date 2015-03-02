<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job\Configuration;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;

class WithoutTaskConfigurationsTest extends ConfigurationTest {

    public function testPersist() {
        $configuration = new Configuration();
        $configuration->setLabel('foo');
        $configuration->setUser($this->getUserService()->getPublicUser());
        $configuration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $configuration->setType(
            $this->getJobTypeService()->getFullSiteType()
        );
        $configuration->setParameters('bar');

        $this->getManager()->persist($configuration);
        $this->getManager()->flush();

        $this->getManager()->clear();

        $configurationId = $configuration->getId();
        $this->assertNotNull($configurationId);
    }
}
