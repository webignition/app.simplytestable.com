<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\GetList;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Model\Job\Configuration\Values as ConfigurationValues;
use SimplyTestable\ApiBundle\Services\JobTypeService;
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


    protected function setUp()
    {
        parent::setUp();

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $fullSiteJobType = $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);

        $userFactory = new UserFactory($this->container);

        $this->leader = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'leader@example.com',
        ]);
        $this->member1 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);
        $this->member2 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $team = $teamService->create(
            'Foo',
            $this->leader
        );

        $teamMemberService->add($team, $this->member1);
        $teamMemberService->add($team, $this->member2);

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
                $jobConfigurationValues->setType($fullSiteJobType);
                $jobConfigurationValues->setWebsite($websiteService->fetch('http://' . $userIndex . '.' . $jobConfigurationIndex . 'example.com/'));
                $jobConfigurationValues->setParameters('parameters');

                $this->jobConfigurations[] = $this->getJobConfigurationService()->create($jobConfigurationValues);
            }
        }

        $entityManager->clear();

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
