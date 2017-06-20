<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\Access;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Job;

abstract class PublicUserAccessTest extends BaseControllerJsonTestCase
{
    const CANONICAL_URL = 'http://www.example.com/';

    /**
     * @var Job
     */
    private $job;

    public function setUp()
    {
        parent::setUp();
        $this->job = $this->createJobFactory()->create();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
    }

    public function testGetForPublicJobOwnedByPublicUserByPublicUser()
    {
        $this->assertTrue($this->job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $this->job->getUser()->getId());

        $actionName = $this->getActionNameFromRouter();
        $this->assertEquals(
            200,
            $this->getCurrentController()->$actionName(self::CANONICAL_URL, $this->job->getId())->getStatusCode()
        );
    }

    public function testGetForPublicJobOwnedByPublicUserByNonPublicUser()
    {
        $this->assertTrue($this->job->getIsPublic());
        $this->assertEquals($this->getUserService()->getPublicUser()->getId(), $this->job->getUser()->getId());

        $user = $this->createAndActivateUser('user@example.com', 'password');
        $actionName = $this->getActionNameFromRouter();

        $this->assertEquals(200, $this->getCurrentController([
            'user' => $user->getEmail()
        ])->$actionName(self::CANONICAL_URL, $this->job->getId())->getStatusCode());
    }

    protected function getRouteParameters()
    {
        return [
            'site_root_url' => self::CANONICAL_URL,
            'test_id' => $this->job->getId()
        ];
    }
}
