<?php

namespace Tests\ApiBundle\Functional\Entity\Job\Configuration\WithTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class PersistTest extends AbstractBaseTestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    protected function setUp()
    {
        parent::setUp();

        $userService = $this->container->get(UserService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get(WebSiteService::class);

        $fullSiteJobType = $jobTypeService->getFullSiteType();

        $this->configuration = new Configuration();
        $this->configuration->setLabel('foo');
        $this->configuration->setUser($userService->getPublicUser());
        $this->configuration->setWebsite($websiteService->get('http://example.com/'));
        $this->configuration->setType($fullSiteJobType);
        $this->configuration->setParameters('bar');

        $entityManager->persist($this->configuration);
        $entityManager->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($this->configuration);
        $taskConfiguration->setType(
            $taskType = $taskTypeService->getHtmlValidationTaskType()
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
