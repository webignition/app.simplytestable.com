<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\GetList;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Collection as JobConfigurationCollection;

class ForTeamTest extends ServiceTest {

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
     * @var JobConfigurationCollection[]
     */
    private $retrievedJobConfigurations = [];


    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com', 'password');
        $this->member1 = $this->createAndActivateUser('user1@example.com');
        $this->member2 = $this->createAndActivateUser('user2@example.com');

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
                $this->jobConfigurations[] = $this->getJobConfigurationService()->create(
                    $this->getWebSiteService()->fetch('http://' . $userIndex . '.' . $jobConfigurationIndex . 'example.com/'),
                    $this->getJobTypeService()->getFullSiteType(),
                    $this->getStandardTaskConfigurationCollection(),
                    self::LABEL . '::' . $userIndex . '::' . $jobConfigurationIndex,
                    'parameters'
                );
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
            $this->retrievedJobConfigurations[$this->people[0]->getEmail()]->count()
        );
    }


    public function testMember1JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT * count($this->people),
            $this->retrievedJobConfigurations[$this->people[1]->getEmail()]->count()
        );
    }


    public function testMember2JobConfigurationCount() {
        $this->assertEquals(
            self::JOB_CONFIGURATION_COUNT * count($this->people),
            $this->retrievedJobConfigurations[$this->people[2]->getEmail()]->count()
        );
    }

}
