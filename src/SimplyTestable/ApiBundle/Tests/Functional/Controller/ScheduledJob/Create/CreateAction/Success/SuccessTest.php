<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\CreateTest;
use Symfony\Component\HttpFoundation\Response;

abstract class SuccessTest extends CreateTest {

    /**
     * @var Response
     */
    private $response;


    /**
     * @var ScheduledJob
     */
    protected $scheduledJob;


    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);

        $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $user);

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request')
        );

        $this->scheduledJob = $this->getScheduledJobService()->get($this->getScheduledJobIdFromResponseLocation());
    }

    public function testResponseStatusCode() {
        $this->assertEquals(302, $this->response->getStatusCode());
    }


    public function testJobConfigurationIsPersisted() {
        $this->assertNotNull($this->scheduledJob->getId());
    }


    public function testResponseRedirectLocation() {
        $this->assertEquals('/scheduledjob/' . $this->scheduledJob->getId() . '/', $this->response->headers->get('location'));
    }

    protected function getScheduledJobIdFromResponseLocation() {
        $matches = [];
        preg_match('/[0-9]+/', $this->response->headers->get('location'), $matches);

        return (int)$matches[0];
    }

}