<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\TeamAccess;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;

abstract class TeamAccessTest extends BaseControllerJsonTestCase {

    /**
     * Created by leader, accessed by member
     * Created by member, accessed by another member
     * Created by member, accessed by leader
     */


    const CANONICAL_URL = 'http://www.example.com/';

    /**
     * @var Job
     */
    protected $job;

    /**
     * @return User
     */
    abstract protected function getJobOwner();

    /**
     * @return User
     */
    abstract protected function getJobAccessor();

    protected function preCreateJob() {
    }

    public function setUp() {
        parent::setUp();

        $this->preCreateJob();

        $this->job = $this->getJobService()->getById($this->createJobAndGetId(
            self::CANONICAL_URL,
            $this->getJobOwner()->getEmail()
        ));
    }

    public function testHasAccess() {
        $this->getUserService()->setUser($this->getJobAccessor());

        $actionName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'user' => $this->getJobAccessor()->getEmail()
        ])->$actionName(self::CANONICAL_URL, $this->job->getId());

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function getRouteParameters() {
        return [
            'site_root_url' => self::CANONICAL_URL,
            'test_id' => $this->job->getId()
        ];
    }

}


