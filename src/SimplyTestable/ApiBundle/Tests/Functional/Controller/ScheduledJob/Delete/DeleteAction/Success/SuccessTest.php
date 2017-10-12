<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete\DeleteAction\Success;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\DeleteController;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete\DeleteAction\DeleteTest;
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

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->createAndActivateUser();
        $this->setUser($user);

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

        $controller = new DeleteController();
        $controller->setContainer($this->container);

        $this->response = $controller->deleteAction($this->scheduledJob->getId());
    }

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testJobConfigurationIsRemoved() {
        $this->assertNull($this->scheduledJob->getId());
    }
}