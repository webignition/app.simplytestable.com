<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\GetList;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ForTeamTest extends ServiceTest
{
    const LABEL = 'foo';
    const JOB_CONFIGURATION_COUNT = 5;

    /**
     * @var User
     */
    private $leader;


    /**
     * @var User
     */
    private $member1;

    /**
     * @var User
     */
    private $member2;


    /**
     * @var User[]
     */
    private $people = [];


    /**
     * @var JobConfiguration[]
     */
    private $jobConfigurations = [];

    /**
     * @var array
     */
    private $retrievedJobConfigurations = [];


    public function setUp()
    {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->leader = $userFactory->createAndActivateUser('leader@example.com');
        $this->member1 = $userFactory->createAndActivateUser('user1@example.com');
        $this->member2 = $userFactory->createAndActivateUser('user2@example.com');

        $team = $this->getTeamService()->create(
            'Foo',
            $this->leader
        );

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $this->people = [
            $this->leader,
            $this->member1,
            $this->member2
        ];

        foreach ($this->people as $userIndex => $user) {
            $this->getJobConfigurationService()->setUser($user);
            for ($jobConfigurationIndex = 0; $jobConfigurationIndex < self::JOB_CONFIGURATION_COUNT; $jobConfigurationIndex++) {
                $jobConfigurationValues = new ConfigurationValues();
                $jobConfigurationValues->setLabel(self::LABEL . '::' . $userIndex . '::' . $jobConfigurationIndex);
                $jobConfigurationValues->setTaskConfigurationCollection($this->getStandardTaskConfigurationCollection());
                $jobConfigurationValues->setType($this->getJobTypeService()->getFullSiteType());
                $jobConfigurationValues->setWebsite($this->getWebSiteService()->fetch('http://' . $userIndex . '.' . $jobConfigurationIndex . 'example.com/'));
                $jobConfigurationValues->setParameters('parameters');

                $this->jobConfigurations[] = $this->getJobConfigurationService()->create($jobConfigurationValues);
            }
        }

        $this->getManager()->clear();

        foreach ($this->people as $userIndex => $user) {
            /* @var $user User */
            $this->getJobConfigurationService()->setUser($user);
            $this->retrievedJobConfigurations[$user->getEmail()] = $this->getJobConfigurationService()->getList(true);
        }
    }

    public function testLeaderJobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT * count($this->people),
            count($this->retrievedJobConfigurations[$this->people[0]->getEmail()])
        );
    }


    public function testMember1JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT * count($this->people),
            count($this->retrievedJobConfigurations[$this->people[1]->getEmail()])
        );
    }


    public function testMember2JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT * count($this->people),
            count($this->retrievedJobConfigurations[$this->people[2]->getEmail()])
        );
    }

}
