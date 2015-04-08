<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\Success;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Update\UpdateAction\UpdateTest;
use Symfony\Component\HttpFoundation\Response;

abstract class SuccessTest extends UpdateTest {

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;


    /**
     * @var ScheduledJob
     */
    private $updatedScheduledJob;


    /**
     * @var Response
     */
    private $response;


    /**
     * @var JobConfiguration
     */
    protected $originalJobConfiguration;


    /**
     * @var string
     */
    protected $originalSchedule = '* * * * *';


    /**
     * @var int
     */
    protected $originalIsRecurring = 1;


    /**
     * @var string|null
     */
    protected $originalCronModifier = null;


    /**
     * @var int
     */
    private $originalScheduledJobId;


    /**
     * @var User
     */
    protected $user;


    public function setUp() {
        parent::setUp();

        $this->user = $this->createAndActivateUser();

        $this->getUserService()->setUser($this->user);

        $this->originalJobConfiguration = $this->createJobConfiguration([
            'label' => 'foo',
            'parameters' => 'parameters',
            'type' => 'Full site',
            'website' => 'http://example.com/',
            'task_configuration' => [
                'HTML validation' => []
            ],

        ], $this->user);

        $this->getScheduledJobService()->setUser($this->user);
        $this->scheduledJob = $this->getScheduledJobService()->create(
            $this->originalJobConfiguration,
            $this->originalSchedule,
            $this->originalCronModifier,
            $this->originalIsRecurring
        );

        $this->originalScheduledJobId = $this->scheduledJob->getId();

        $this->preCallController();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request'),
            $this->getScheduledJobId()
        );

        $this->getManager()->clear();

        $this->updatedScheduledJob = $this->getScheduledJobService()->getEntityRepository()->find($this->originalScheduledJobId);
    }

    abstract protected function getNewJobConfigurationLabel();
    abstract protected function getNewSchedule();
    abstract protected function getNewIsRecurring();
    abstract protected function getNewCronModifier();

    protected function getScheduledJobId() {
        return $this->scheduledJob->getId();
    }

    protected function preCallController() {}

    public function testResponseStatusCodeIs302() {
        $this->assertEquals(302, $this->response->getStatusCode());
    }

    public function testResponseRedirectLocation() {
        $this->assertEquals('/scheduledjob/' . $this->scheduledJob->getId() . '/', $this->response->headers->get('location'));
    }

    public function testUpdatedScheduledJobProperties() {
        $this->assertEquals($this->updatedScheduledJob->getJobConfiguration()->getLabel(), $this->getNewJobConfigurationLabel());
        $this->assertEquals($this->updatedScheduledJob->getCronJob()->getSchedule(), $this->getNewSchedule());
        $this->assertEquals($this->updatedScheduledJob->getIsRecurring(), $this->getNewIsRecurring());
    }

}