<?php

namespace Tests\ApiBundle\Functional\Controller\Task;

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
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Factory\InternetMediaTypeFactory;
use Tests\ApiBundle\Factory\TaskControllerCompleteActionRequestFactory;
use Tests\ApiBundle\Factory\TaskTypeFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class TaskControllerTaskTypeCountActionTest extends AbstractBaseTestCase
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
        $this->expectException(NotFoundHttpException::class);

        $this->taskController->taskTypeCountAction('foo', 'completed');
    }

    public function testTaskTypeCountActionInvalidState()
    {
        $this->expectException(NotFoundHttpException::class);

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
