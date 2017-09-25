<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CookieParametersTest extends PreProcessorTest {

    protected function setUp() {
        parent::setUp();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $this->tasks->get(1)->getId()
        ]), new BufferedOutput());
    }

    protected function getJobParameters() {
        return array(
            'cookies' => array(
                array(
                    'domain' => '.example.com',
                    'name' => 'foo',
                    'value' => 'bar'
                )
            )
        );
    }

    protected function getCompletedTaskOutput() {
        return $this->getDefaultCompletedTaskOutput();
    }

    public function testCookiesAreSetOnRequests() {
        foreach ($this->getHttpClientService()->getHistoryPlugin()->getAll() as $httpTransaction) {
            $this->assertEquals(array(
                'foo' => 'bar'
            ), $httpTransaction['request']->getCookies());
        }
    }

}
