<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Task;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\InternetMediaTypeFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskTypeFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskControllerTaskTypeCountActionTest extends BaseSimplyTestableTestCase
{
    /**
     * @var TaskController
     */
    private $taskController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskController = new TaskController();
        $this->taskController->setContainer($this->container);
    }

    public function testTaskTypeCountActionInvalidTaskType()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->taskController->taskTypeCountAction('foo', 'completed');
    }

    public function testTaskTypeCountActionInvalidState()
    {
        $this->setExpectedException(NotFoundHttpException::class);

        $this->taskController->taskTypeCountAction(TaskTypeService::HTML_VALIDATION_TYPE, 'foo');
    }

    public function testTaskTypeCountActionSuccess()
    {
        $response = $this->taskController->taskTypeCountAction(TaskTypeService::HTML_VALIDATION_TYPE, 'completed');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent());

        $this->assertEquals(0, $responseData);
    }
}
