<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\Access\TeamAccess;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class TeamAccessTest extends BaseControllerJsonTestCase
{
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

    protected function preCreateJob()
    {
    }

    public function setUp()
    {
        parent::setUp();

        $this->preCreateJob();

        $this->job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $this->getJobOwner(),
        ]);
    }

    public function testHasAccess()
    {
        $this->getUserService()->setUser($this->getJobAccessor());

        $actionName = $this->getActionNameFromRouter();
        $response = $this->getCurrentController([
            'user' => $this->getJobAccessor()->getEmail()
        ])->$actionName(self::CANONICAL_URL, $this->job->getId());

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function getRouteParameters()
    {
        return [
            'site_root_url' => self::CANONICAL_URL,
            'test_id' => $this->job->getId()
        ];
    }
}
