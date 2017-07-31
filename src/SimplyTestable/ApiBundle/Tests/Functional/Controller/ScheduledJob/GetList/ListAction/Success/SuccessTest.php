<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\GetList\ListAction\Success;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\GetList\ListAction\GetListTest;
use Symfony\Component\HttpFoundation\Response;

abstract class SuccessTest extends GetListTest {

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

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->createAndActivateUser();

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
        $this->scheduledJob = $this->getScheduledJobService()->create(
            $jobConfiguration,
            '* * * * *',
            $this->getCronModifier(),
            true
        );


        $this->response = $this->getCurrentController()->$methodName();
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
            'jobconfiguration' => 'foo',
            'schedule' => '* * * * *',
            'isrecurring' => 1
        ];

        if (!is_null($this->getCronModifier())) {
            $expectedResponseData['schedule-modifier'] = $this->scheduledJob->getCronModifier();
        }

        $this->assertEquals([
            $expectedResponseData
        ], $this->decodedResponse);
    }
}