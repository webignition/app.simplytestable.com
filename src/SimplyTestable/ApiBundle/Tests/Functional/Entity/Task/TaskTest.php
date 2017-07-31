<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Task;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskTest extends BaseSimplyTestableTestCase
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

    public function testUtf8Url()
    {
        $taskUrl = 'http://example.com/ɸ';

        $task = new Task();
        $task->setJob($this->jobFactory->create());
        $task->setUrl($taskUrl);
        $task->setState($this->getTaskService()->getQueuedState());
        $task->setType($this->getTaskTypeService()->getByName('HTML Validation'));

        $this->getManager()->persist($task);
        $this->getManager()->flush();

        $taskId = $task->getId();

        $this->getManager()->clear();

        $this->assertEquals(
            $taskUrl,
            $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Task')->find($taskId)->getUrl()
        );
    }

    public function testUtf8Parameters()
    {
        $key = 'key-ɸ';
        $value = 'value-ɸ';

        $task = new Task();
        $task->setJob($this->jobFactory->create());
        $task->setUrl('http://example.com/');
        $task->setState($this->getTaskService()->getQueuedState());
        $task->setType($this->getTaskTypeService()->getByName('HTML Validation'));
        $task->setParameters(json_encode(array(
            $key => $value
        )));

        $this->getManager()->persist($task);
        $this->getManager()->flush();

        $taskId = $task->getId();

        $this->getManager()->clear();

        $this->assertEquals(
            '{"key-\u0278":"value-\u0278"}',
            $this->getTaskService()->getById($taskId)->getParameters()
        );
    }
}
