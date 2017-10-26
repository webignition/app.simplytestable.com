<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job\Configuration\WithTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class PersistTest extends BaseSimplyTestableTestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    protected function setUp()
    {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $this->configuration = new Configuration();
        $this->configuration->setLabel('foo');
        $this->configuration->setUser($userService->getPublicUser());
        $this->configuration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $this->configuration->setType($fullSiteJobType);
        $this->configuration->setParameters('bar');

        $entityManager->persist($this->configuration);
        $entityManager->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($this->configuration);
        $taskConfiguration->setType(
            $this->getTaskTypeService()->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $entityManager->persist($taskConfiguration);
        $entityManager->flush();

        $this->configuration->addTaskConfiguration($taskConfiguration);

        $entityManager->persist($this->configuration);
        $entityManager->flush();
    }

    public function testConfigurationIsPersisted()
    {
        $this->assertNotNull($this->configuration->getId());
    }

    public function testTaskConfigurationsExist()
    {
        $this->assertEquals(1, count($this->configuration->getTaskConfigurations()));
    }

    public function testTaskConfigurationsArePersisted()
    {
        /* @var $taskConfiguration TaskConfiguration */
        foreach ($this->configuration->getTaskConfigurations() as $taskConfiguration) {
            $this->assertNotNull($taskConfiguration->getId());
        }
    }
}
