<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction\Success;

class TaskConfigurationTest extends SuccessTest {

    public function setUp() {
        parent::setUp();
    }

    protected function getNewLabel() {
        return '';
    }

    protected function getNewWebsite() {
        return $this->getOriginalWebsite();
    }

    protected function getNewJobType() {
        return $this->getOriginalJobType();
    }

    protected function getNewTaskConfiguration() {
        return [
            'HTML validation' => [
                'foo' => 'bar'
            ],
            'CSS validation' => [
                'foo' => 'bar'
            ],
        ];
    }

    protected function getNewParameters() {
        return $this->getOriginalParameters();
    }
}