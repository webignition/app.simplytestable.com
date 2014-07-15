<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\TeamTest;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Team;

abstract class TeamTest extends BaseControllerJsonTestCase {

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
    private $jobIds = [];


    /**
     * @var string[]
     */
    private $websites;

    public function setUp() {
        parent::setUp();

        $this->leader = $this->createAndActivateUser('leader@example.com');
        $this->member1 = $this->createAndActivateUser('member1@example.com');
        $this->member2 = $this->createAndActivateUser('member2@example.com');

        $team = $this->getTeamService()->create('Foo', $this->leader);

        $this->getTeamMemberService()->add($team, $this->member1);
        $this->getTeamMemberService()->add($team, $this->member2);

        $this->jobIds[] = $this->createJobAndGetId('http://leader.example.com/', $this->leader->getEmail());
        $this->jobIds[] = $this->createJobAndGetId('http://member1.example.com/', $this->member1->getEmail());
        $this->jobIds[] = $this->createJobAndGetId('http://member2.example.com/', $this->member2->getEmail());

        $websitesResponse = $this->getJobListController('websitesAction', [
            'user' => $this->getRequester()->getEmail()
        ])->websitesAction(count($this->jobIds));

        $this->websites = json_decode($websitesResponse->getContent());
    }


    /**
     * @return User
     */
    abstract protected function getRequester();


    public function testWebsitesList() {
        $this->assertEquals([
            'http://leader.example.com/',
            'http://member1.example.com/',
            'http://member2.example.com/'
        ], $this->websites);
    }
    
}