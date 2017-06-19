<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\TeamTest;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class TeamTest extends BaseControllerJsonTestCase
{
    /**
     * @var User
     */
    protected $leader;


    /**
     * @var User
     */
    protected $member1;


    /**
     * @var User
     */
    protected $member2;


    /**
     * @var array
     */
    private $jobs = [];


    /**
     * @var string[]
     */
    private $websites;

    public function setUp()
    {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('member1@example.com');
        $this->member2 = $this->createAndActivateUser('member2@example.com');

        $this->getUserService()->setUser($this->getRequester());

        $team = $this->getTeamService()->create('Foo', $this->leader);

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $jobFactory = $this->createJobFactory();
        $this->jobs[] = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://leader.example.com/',
            JobFactory::KEY_USER => $this->leader,
        ]);
        $this->jobs[] = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://member1.example.com/',
            JobFactory::KEY_USER => $this->member1,
        ]);
        $this->jobs[] = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://member2.example.com/',
            JobFactory::KEY_USER => $this->member2,
        ]);

        $websitesResponse = $this->getJobListController('websitesAction')->websitesAction();

        $this->websites = json_decode($websitesResponse->getContent());
    }

    /**
     * @return User
     */
    abstract protected function getRequester();

    public function testWebsitesList()
    {
        $this->assertEquals([
            'http://leader.example.com/',
            'http://member1.example.com/',
            'http://member2.example.com/'
        ], $this->websites);
    }
}
