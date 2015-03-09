<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success\User;

use SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success\SuccessTest;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class UserTest extends SuccessTest {

    private $taskTypeOptionsSet = [
        'CSS validation' => [
            'options' => [
                'css+foo' => 'css+bar'
            ]
        ],
        'JS static analysis' => [
            'options' => [
                'js+foo' => 'js+bar'
            ]
        ],
        'Link integrity' => [
            'options' => [
                'li+foo' => 'li+bar'
            ]
        ]
    ];

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getOriginalWebsite() {
        return $this->getWebSiteService()->fetch('http://original.example.com/');
    }

    protected function getOriginalJobType() {
        return $this->getJobTypeService()->getFullSiteType();
    }

    protected function getOriginalParameters() {
        return 'original-parameters';
    }

    protected function getNewWebsite() {
        return $this->getWebSiteService()->fetch('http://new.example.com/');
    }

    protected function getNewJobType() {
        return $this->getJobTypeService()->getSingleUrlType();
    }

    protected function getNewParameters() {
        return 'new-parameters';
    }

    protected function getNewTaskConfigurationCollection() {
        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $this->getTaskTypeService()->getByName($taskTypeName)
            );
            $taskConfiguration->setOptions($taskTypeOptions['options']);

            $taskConfigurationCollection->add($taskConfiguration);
        }

        return $taskConfigurationCollection;
    }

    protected function getNewLabel() {
        return 'new-foo';
    }
}