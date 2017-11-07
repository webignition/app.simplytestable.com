<?php

namespace Tests\ApiBundle\Functional\Entity\Job\TaskConfiguration;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Type;
use SimplyTestable\ApiBundle\Services\JobTypeService;

class PersistTest extends TaskConfigurationTest
{
    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration;

    protected function setUp()
    {
        parent::setUp();

        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobTypeRepository = $this->container->get('simplytestable.repository.jobtype');

        /* @var Type $fullSiteJobType */
        $fullSiteJobType = $jobTypeRepository->findOneBy([
            'name' => JobTypeService::FULL_SITE_NAME,
        ]);

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setLabel('foo');
        $jobConfiguration->setUser($userService->getPublicUser());
        $jobConfiguration->setWebsite(
            $this->container->get('simplytestable.services.websiteservice')->fetch('http://example.com/')
        );
        $jobConfiguration->setType($fullSiteJobType);
        $jobConfiguration->setParameters('bar');

        $entityManager->persist($jobConfiguration);
        $entityManager->flush();

        $taskConfiguration = new TaskConfiguration();
        $taskConfiguration->setJobConfiguration($jobConfiguration);
        $taskConfiguration->setType(
            $taskTypeService->getByName('HTML validation')
        );
        $taskConfiguration->setOptions([
            'foo' => 'bar'
        ]);

        $entityManager->persist($taskConfiguration);
        $entityManager->flush();

        $taskConfigurationId = $taskConfiguration->getId();

        $entityManager->clear();

        $taskConfigurationRepository = $this->container->get('simplytestable.repository.taskconfiguration');

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
