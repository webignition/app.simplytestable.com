<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job\Configuration\WithoutTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Services\JobTypeService;

class PersistTest extends WithoutTaskConfigurationsTest
{

    /**
     * @var Configuration
     */
    private $configuration;

    protected function setUp()
    {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);
        $userService = $this->container->get('simplytestable.services.userservice');

        $this->configuration = new Configuration();
        $this->configuration->setLabel('foo');
        $this->configuration->setUser($userService->getPublicUser());
        $this->configuration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $this->configuration->setType($fullSiteJobType);
        $this->configuration->setParameters('bar');

        $this->getManager()->persist($this->configuration);
        $this->getManager()->flush();
    }


    public function testIsPersisted()
    {
        $this->assertNotNull($this->configuration->getId());
    }
}
