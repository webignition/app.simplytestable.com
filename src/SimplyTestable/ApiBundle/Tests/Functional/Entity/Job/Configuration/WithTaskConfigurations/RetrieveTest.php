<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job\Configuration\WithTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Services\JobTypeService;

class RetrieveTest extends WithTaskConfigurationsTest
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

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $userService = $this->container->get('simplytestable.services.userservice');
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
