<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

class SetExcludedUrlsWhenExcludedDomainsParameterExistsTest extends ExcludedUrlsTest {

    protected function setUp() {
        parent::setUp();

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $this->tasks->get(1)->getId()
        ));
    }

    protected function getTestTypeOptions() {
        return array('Link integrity' => array(
            'excluded-domains' => array(
                'instagram.com'
            )
        ));
    }

    public function test1thTaskHasCorrectExcludedUrls() {
        $this->assertEquals(array(
            'excluded-urls' => array(
                'http://example.com/three',
                'http://example.com/two'
            ),
            'excluded-domains' => array(
                'instagram.com'
            )
        ), json_decode($this->tasks->get(1)->getParameters(), true));
    }


}
