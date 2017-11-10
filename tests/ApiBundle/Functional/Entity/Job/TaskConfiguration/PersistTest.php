<?php

namespace Tests\ApiBundle\Functional\Entity\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;

class PersistTest extends TaskConfigurationTest
{
    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration;

    protected function setUp()
    {
        parent::setUp();

        $userService = $this->container->get(UserService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $websiteService = $this->container->get(WebSiteService::class);

        $fullSiteJobType = $jobTypeService->getFullSiteType();

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel('foo');
        $jobConfiguration->setUser($userService->getPublicUser());
        $jobConfiguration->setWebsite($websiteService->get('http://example.com/'));
        $jobConfiguration->setType($fullSiteJobType);
        $jobConfiguration->setParameters('bar');

        $entityManager->persist($jobConfiguration);
        $entityManager->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($jobConfiguration);
        $taskConfiguration->setType(
            $taskType = $taskTypeService->getHtmlValidationTaskType()
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $entityManager->persist($taskConfiguration);
        $entityManager->flush();

        $taskConfigurationId = $taskConfiguration->getId();

        $entityManager->clear();

        $taskConfigurationRepository = $entityManager->getRepository(TaskConfiguration::class);

        $this->taskConfiguration = $taskConfigurationRepository->find($taskConfigurationId);
    }

    public function testIsPersisted()
    {
        $this->assertNotNull($this->taskConfiguration->getId());
    }

    public function testDefaultIsEnabled()
    {
        $this->assertTrue($this->taskConfiguration->getIsEnabled());
    }
}
