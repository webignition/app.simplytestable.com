<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Get\GetAction\Success;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\GetController;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\ActionTest;
use Symfony\Component\HttpFoundation\Response;

abstract class SuccessTest extends ActionTest {

    /**
     * @var Response
     */
    private $response;


    /**
     * @var ScheduledJob
     */
    private $scheduledJob;


    private $decodedResponse;

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->createAndActivateUser();

        $this->setUser($user);
        $this->getScheduledJobService()->setUser($user);
        $this->getJobConfigurationService()->setUser($user);

        $jobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $user);

        $this->scheduledJob = $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * *',
            $this->getCronModifier(),
            true
        );

        $controller = new GetController();
        $controller->setContainer($this->container);

        $this->response = $controller->getAction($this->scheduledJob->getId());
        $this->decodedResponse = json_decode($this->response->getContent(), true);
    }

    /**
     * @return string|null
     */
    abstract protected function getCronModifier();

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }


    public function testDecodedResponseContent() {
        $expectedResponseData = [
            'id' => $this->scheduledJob->getId(),
            'jobconfiguration' =>  $this->scheduledJob->getJobConfiguration()->getLabel(),
            'schedule' => $this->scheduledJob->getCronJob()->getSchedule(),
            'isrecurring' => 1
        ];

        if (!is_null($this->getCronModifier())) {
            $expectedResponseData['schedule-modifier'] = $this->scheduledJob->getCronModifier();
        }

        $this->assertEquals($expectedResponseData, $this->decodedResponse);
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'id' => '1'
        ];
    }
}