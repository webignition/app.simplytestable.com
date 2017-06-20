<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

class CookieParametersTest extends PreProcessorTest {

    public function setUp() {
        parent::setUp();

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $this->tasks->get(1)->getId()
        ));
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
