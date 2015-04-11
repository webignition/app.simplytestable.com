<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Get\GetAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Get\GetAction\GetTest;
use Symfony\Component\HttpFoundation\Response;

abstract class SuccessTest extends GetTest {

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

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName($this->scheduledJob->getId());
        $this->decodedResponse = json_decode($this->response->getContent(), true);
    }

    /**
     * @return string|null
     */
    abstract protected function getCronModifier();

    /**
     * @return bool
     */
    abstract protected function isExpectingCronModifier();

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

        if ($this->isExpectingCronModifier()) {
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