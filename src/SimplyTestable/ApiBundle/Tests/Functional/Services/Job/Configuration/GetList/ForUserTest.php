<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\GetList;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ForUserTest extends ServiceTest
{
    const LABEL = 'foo';
    const JOB_CONFIGURATION_COUNT = 5;

    /**
     * @var User
     */
    private $user1;

    /**
     * @var User
     */
    private $user2;

    /**
     * @var JobConfiguration[]
     */
    private $jobConfigurations = [];

    /**
     * @var array
     */
    private $retrievedJobConfigurations = [];


    protected function setUp()
    {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $userFactory = new UserFactory($this->container);

        $this->user1 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $this->user2 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $this->getJobConfigurationService()->setUser($this->user1);
        for ($jobConfigurationIndex = 0; $jobConfigurationIndex < self::JOB_CONFIGURATION_COUNT; $jobConfigurationIndex++) {
            $jobConfigurationValues = new ConfigurationValues();
            $jobConfigurationValues->setLabel(self::LABEL . '::' . $jobConfigurationIndex);
            $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
            $jobConfigurationValues->setType($fullSiteJobType);
            $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://' . $jobConfigurationIndex . 'example.com/'));
            $jobConfigurationValues->setParameters('parameters');

            $this->jobConfigurations[] = $this->getJobConfigurationService()->create($jobConfigurationValues);
        }

        $this->getJobConfigurationService()->setUser($this->user2);
        for ($jobConfigurationIndex = 0; $jobConfigurationIndex < self::JOB_CONFIGURATION_COUNT; $jobConfigurationIndex++) {
            $jobConfigurationValues = new ConfigurationValues();
            $jobConfigurationValues->setLabel(self::LABEL . '::' . $jobConfigurationIndex);
            $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
            $jobConfigurationValues->setType($fullSiteJobType);
            $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://' . $jobConfigurationIndex . 'example.com/'));
            $jobConfigurationValues->setParameters('parameters');

            $this->jobConfigurations[] = $this->getJobConfigurationService()->create($jobConfigurationValues);
        }

        $entityManager->clear();

        $this->getJobConfigurationService()->setUser($this->user1);
        $this->retrievedJobConfigurations[$this->user1->getEmail()] = $this->getJobConfigurationService()->getList();

        $this->getJobConfigurationService()->setUser($this->user2);
        $this->retrievedJobConfigurations[$this->user2->getEmail()] = $this->getJobConfigurationService()->getList();
    }


    public function testUser1JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT,
            count($this->retrievedJobConfigurations[$this->user1->getEmail()])
        );
    }

    public function testUser2JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT,
            count($this->retrievedJobConfigurations[$this->user1->getEmail()])
        );
    }

    public function testOwnershipOfJobConfigurationsForUser1() {
        foreach ($this->retrievedJobConfigurations[$this->user1->getEmail()] as $jobConfiguration) {
            /* @var $jobConfiguration JobConfiguration */
            $this->assertEquals($this->user1->getEmail(), $jobConfiguration->getUser()->getEmail());
        }
    }


    public function testOwnershipOfJobConfigurationsForUser2() {
        foreach ($this->retrievedJobConfigurations[$this->user2->getEmail()] as $jobConfiguration) {
            /* @var $jobConfiguration JobConfiguration */
            $this->assertEquals($this->user2->getEmail(), $jobConfiguration->getUser()->getEmail());
        }
    }
}
