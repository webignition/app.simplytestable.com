<?php

namespace Tests\ApiBundle\Functional\Entity\Job\Configuration\WithTaskConfigurations;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class RetrieveTest extends AbstractBaseTestCase
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

        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $configurationRepository = $this->container->get('simplytestable.repository.jobconfiguration');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

        $fullSiteJobType = $jobTypeService->getFullSiteType();

        $this->originalConfiguration = new Configuration();
        $this->originalConfiguration->setLabel('foo');
        $this->originalConfiguration->setUser($userService->getPublicUser());
        $this->originalConfiguration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $this->originalConfiguration->setType($fullSiteJobType);
        $this->originalConfiguration->setParameters('bar');

        $entityManager->persist($this->originalConfiguration);
        $entityManager->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($this->originalConfiguration);
        $taskConfiguration->setType(
            $taskTypeService->get('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $entityManager->persist($taskConfiguration);
        $entityManager->flush();

        $this->originalConfiguration->addTaskConfiguration($taskConfiguration);

        $entityManager->persist($this->originalConfiguration);
        $entityManager->flush();

        $this->configurationId = $this->originalConfiguration->getId();

        $entityManager->clear();

        $this->retrievedConfiguration = $configurationRepository->find($this->configurationId);
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

    public function testRetrievedHasTaskConfigurations()
    {
        $this->assertEquals(1, count($this->retrievedConfiguration->getTaskConfigurations()));
    }
}
