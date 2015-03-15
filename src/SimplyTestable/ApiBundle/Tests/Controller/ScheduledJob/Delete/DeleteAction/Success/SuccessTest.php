<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Delete\DeleteAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Delete\DeleteAction\DeleteTest;
use Symfony\Component\HttpFoundation\Response;

class SuccessTest extends DeleteTest {

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

        $this->scheduledJob = $this->getScheduledJobService()->create($jobConfiguration, '* * * * *', true);
        $this->response = $this->getCurrentController()->$methodName($this->scheduledJob->getId());
    }

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testJobConfigurationIsRemoved() {
        $this->assertNull($this->scheduledJob->getId());
    }
}