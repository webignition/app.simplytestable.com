<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction;

abstract class SingleUserTest extends CountTest {

    protected $count;

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job[]
     */
    protected $jobs = array();

    public function setUp() {
        parent::setUp();

        $this->count = json_decode($this->getJobListController(
            'countAction',
            $this->getPostParameters(),
            $this->getQueryParameters())->countAction()->getContent()
        );
    }

    abstract protected function getQueryParameters();

    protected function getPostParameters() {
        return array();
    }

    protected function getLimit() {
        return max(1, count($this->getCanonicalUrls()));
    }
}