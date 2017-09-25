<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SetUserAgentWhenGettingWebResourceTest extends ExcludedUrlsTest {

    protected function setUp() {
        parent::setUp();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $this->tasks->get(1)->getId()
        ]), new BufferedOutput());
    }

    public function testHttpRequestHistoryUserAgent() {
        $this->assertEquals(
            'ST Link integrity task pre-processor',
            (string)$this->getHttpClientService()->getHistoryPlugin()->getAll()[3]['request']->getHeader('user-agent')
        );
    }

}
