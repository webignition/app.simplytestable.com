<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class LinkIntegrityTaskOutputJoinerServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
    }

    public function testJoinOnComplete()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['link integrity'],
        ]);

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(
                'text/html',
                '<!DOCTYPE html>
                       <html lang="en">
                           <body>
                               <a href="http://example.com/three">Another Example Three</a>
                               <a href="http://example.com/one">Another Example One</a>
                               <a href="http://example.com/two">Another Example Two</a>
                               <a href="http://example.com/four">Example Four</a>
                           </body>
                       </html>'
            ),
            HttpFixtureFactory::createSuccessResponse(
                'application/json',
                json_encode([])
            )
        ]);

        $tasks = $job->getTasks();

        $now = new \DateTime();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
                array(
                    'context' => '<a href="http://example.com/two">Example Two</a>',
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/two'
                ),
                array(
                    'context' => '<a href="http://example.com/three">Example Three</a>',
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[0]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[0]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
        ]);

        $taskController = new TaskController();
        $taskController->setContainer($this->container);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);

        $taskController->completeAction();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $tasks[1]->getId()
        ]), new BufferedOutput());

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
                array(
                    'context' => '<a href="http://example.com/four">Example Four</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/four'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[1]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[1]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[1]->getParametersHash(),
        ]);

        $taskController = new TaskController();
        $taskController->setContainer($this->container);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);

        $taskController->completeAction();

        $this->assertEquals(2, $tasks[1]->getOutput()->getErrorCount());
    }

    public function testJoinGetsCorrectErrorCount()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['link integrity'],
        ]);

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(
                'text/html',
                '<!DOCTYPE html>
                       <html lang="en">
                           <body>
                               <a href="http://example.com/three">Another Example Three</a>
                               <a href="http://example.com/one">Another Example One</a>
                               <a href="http://example.com/two">Another Example Two</a>
                               <a href="http://example.com/four">Example Four</a>
                           </body>
                       </html>'
            ),
            HttpFixtureFactory::createSuccessResponse(
                'application/json',
                json_encode([])
            )
        ]);

        $tasks = $job->getTasks();

        $now = new \DateTime();

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
                array(
                    'context' => '<a href="http://example.com/two">Example Two</a>',
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/two'
                ),
                array(
                    'context' => '<a href="http://example.com/three">Example Three</a>',
                    'state' => 204,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[0]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[0]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[0]->getParametersHash(),
        ]);

        $taskController = new TaskController();
        $taskController->setContainer($this->container);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);

        $taskController->completeAction();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $tasks[1]->getId()
        ]), new BufferedOutput());

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
                array(
                    'context' => '<a href="http://example.com/four">Example Four</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/four'
                )
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $tasks[1]->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $tasks[1]->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $tasks[1]->getParametersHash(),
        ]);

        $taskController = new TaskController();
        $taskController->setContainer($this->container);

        $this->container->get('request_stack')->push($taskCompleteRequest);
        $this->container->get('simplytestable.services.request.factory.task.complete')->init($taskCompleteRequest);

        $taskController->completeAction();

        $this->assertEquals(2, $tasks[1]->getOutput()->getErrorCount());
    }
}
