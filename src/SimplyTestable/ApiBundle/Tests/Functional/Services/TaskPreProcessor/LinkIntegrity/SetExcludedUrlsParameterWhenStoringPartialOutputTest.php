<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SetExcludedUrlsParameterWhenStoringPartialOutputTest extends ExcludedUrlsTest {

    protected function setUp() {
        parent::setUp();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $this->tasks->get(1)->getId()
        ]), new BufferedOutput());
    }

    public function test1thTaskHasCorrectExcludedUrls() {
        $this->assertEquals(array(
            'excluded-urls' => array(
                'http://example.com/three',
                'http://example.com/two'

            )
        ), json_decode($this->tasks->get(1)->getParameters(), true));
    }

}
