<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\GetList\ListAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\GetList\ListAction\GetListTest;
use Symfony\Component\HttpFoundation\Response;

class SuccessTest extends GetListTest {

    /**
     * @var Response
     */
    private $response;


    /**
     * @var ScheduledJob
     */
    private $scheduledJob;


    private $decodedResponse;

    public function setUp() {
        parent::setUp();

        $user = $this->createAndActivateUser('user@example.com');

        $this->getUserService()->setUser($user);

        $methodName = $this->getActionNameFromRouter();

        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $user);

        $this->getScheduledJobService()->setUser($user);
        $this->scheduledJob = $this->getScheduledJobService()->create($jobConfiguration, '* * * * *', true);

        $this->response = $this->getCurrentController()->$methodName();
        $this->decodedResponse = json_decode($this->response->getContent(), true);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }


    public function testDecodedResponseContent() {
        $this->assertEquals([
            [
                'id' => $this->scheduledJob->getId(),
                'jobconfiguration' => 'foo',
                'schedule' => '* * * * *'
            ]
        ], $this->decodedResponse);
    }
}