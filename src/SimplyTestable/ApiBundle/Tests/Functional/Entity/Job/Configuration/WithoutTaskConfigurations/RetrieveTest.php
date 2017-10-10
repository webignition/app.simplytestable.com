<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job\Configuration\WithoutTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Services\JobTypeService;

class RetrieveTest extends WithoutTaskConfigurationsTest
{

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

    protected function setUp()
    {
        parent::setUp();

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $userService = $this->container->get('simplytestable.services.userservice');

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $this->originalConfiguration = new Configuration();
        $this->originalConfiguration->setLabel('foo');
        $this->originalConfiguration->setUser($userService->getPublicUser());
        $this->originalConfiguration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $this->originalConfiguration->setType($fullSiteJobType);
        $this->originalConfiguration->setParameters('bar');

        $this->getManager()->persist($this->originalConfiguration);
        $this->getManager()->flush();

        $this->configurationId = $this->originalConfiguration->getId();
        $this->getManager()->clear();

        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);
        $this->retrievedConfiguration = $jobConfigurationRepository->find($this->configurationId);
    }

    public function testOriginalAndRetrievedAreNotTheExactSameObject()
    {
        $this->assertNotEquals(
            spl_object_hash($this->originalConfiguration),
            spl_object_hash($this->retrievedConfiguration)
        );
    }

    public function testOriginalAndRetrievedAreTheSameEntity()
    {
        $this->assertEquals($this->originalConfiguration->getId(), $this->retrievedConfiguration->getId());
    }
}
