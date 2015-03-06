<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\GetList;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Collection as JobConfigurationCollection;

class ForUserTest extends ServiceTest {

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
     * @var JobConfigurationCollection[]
     */
    private $retrievedJobConfigurations = [];


    public function setUp() {
        parent::setUp();

        $this->user1 = $this->createAndActivateUser('user1@example.com');
        $this->user2 = $this->createAndActivateUser('user2@example.com');

        $this->getJobConfigurationService()->setUser($this->user1);
        for ($jobConfigurationIndex = 0; $jobConfigurationIndex < self::JOB_CONFIGURATION_COUNT; $jobConfigurationIndex++) {
            $this->jobConfigurations[] = $this->getJobConfigurationService()->create(
                $this->getWebSiteService()->fetch('http://' . $jobConfigurationIndex . 'example.com/'),
                $this->getJobTypeService()->getFullSiteType(),
                $this->getStandardTaskConfigurationCollection(),
                self::LABEL . '::' . $jobConfigurationIndex,
                'parameters'
            );
        }

        $this->getJobConfigurationService()->setUser($this->user2);
        for ($jobConfigurationIndex = 0; $jobConfigurationIndex < self::JOB_CONFIGURATION_COUNT; $jobConfigurationIndex++) {
            $this->jobConfigurations[] = $this->getJobConfigurationService()->create(
                $this->getWebSiteService()->fetch('http://' . $jobConfigurationIndex . 'example.com/'),
                $this->getJobTypeService()->getFullSiteType(),
                $this->getStandardTaskConfigurationCollection(),
                self::LABEL . '::' . $jobConfigurationIndex,
                'parameters'
            );
        }

        $this->getManager()->clear();

        $this->getJobConfigurationService()->setUser($this->user1);
        $this->retrievedJobConfigurations[$this->user1->getEmail()] = $this->getJobConfigurationService()->getList();

        $this->getJobConfigurationService()->setUser($this->user2);
        $this->retrievedJobConfigurations[$this->user2->getEmail()] = $this->getJobConfigurationService()->getList();
    }


    public function testUser1JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT,
            $this->retrievedJobConfigurations[$this->user1->getEmail()]->count()
        );
    }

    public function testUser2JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT,
            $this->retrievedJobConfigurations[$this->user1->getEmail()]->count()
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
