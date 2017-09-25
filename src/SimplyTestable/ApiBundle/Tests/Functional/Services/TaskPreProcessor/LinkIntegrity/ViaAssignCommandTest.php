<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ViaAssignCommandTest extends PreProcessorTest {

    protected function setUp() {
        parent::setUp();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $this->tasks->get(1)->getId()
        ]), new BufferedOutput());
    }

    protected function getCompletedTaskOutput() {
        return $this->getDefaultCompletedTaskOutput();
    }

    public function testDetermineOutputFromPriorRecentTests() {
        $this->assertEquals(array(
            array(
                'context' => '<a href="http://example.com/three">Another Example Three</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            ),
            array(
                'context' => '<a href="http://example.com/one">Another Example One</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/one'
            ),
            array(
                'context' => '<a href="http://example.com/two">Another Example Two</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/two'
            )
        ), json_decode($this->tasks->get(1)->getOutput()->getOutput(), true));
    }

    public function testDetermineCorrectErrorCount() {
        $this->assertEquals(0, $this->tasks->get(1)->getOutput()->getErrorCount());
    }


    public function testStorePartialTaskOutputBeforeAssign() {
        $this->assertTrue($this->tasks->get(1)->hasOutput());
    }

}
