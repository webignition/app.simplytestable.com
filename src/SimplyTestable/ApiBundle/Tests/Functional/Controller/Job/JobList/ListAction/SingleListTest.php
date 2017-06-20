<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction;

abstract class SingleListTest extends ListTest {

    protected $list;

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job[]
     */
    protected $jobs = array();

    public function setUp() {
        parent::setUp();

        $this->list = json_decode($this->getJobListController(
            'listAction',
            $this->getPostParameters(),
            $this->getQueryParameters())->listAction(
                $this->getLimit()
            )->getContent()
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