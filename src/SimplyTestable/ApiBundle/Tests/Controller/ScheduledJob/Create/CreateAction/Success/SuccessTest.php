<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\CreateTest;
use Symfony\Component\HttpFoundation\Response;

class SuccessTest extends CreateTest {

    /**
     * @var Response
     */
    private $response;


    /**
     * @var ScheduledJob
     */
    private $scheduledJob;


    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

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


    protected function getRequestPostData() {
        return [
            'job-configuration' => 'foo',
            'schedule' => '* * * * *',
            'is-recurring' =>  '1'
        ];
    }

    protected function getScheduledJobIdFromResponseLocation() {
        $matches = [];
        preg_match('/[0-9]+/', $this->response->headers->get('location'), $matches);

        return (int)$matches[0];
    }

}