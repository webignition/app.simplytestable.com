<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\Success\User;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\Success\SuccessTest;
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
        $userService = $this->container->get('simplytestable.services.userservice');
        return $userService->getPublicUser();
    }

    protected function getOriginalWebsite() {
        $websiteService = $this->container->get('simplytestable.services.websiteservice');

        return $websiteService->fetch('http://original.example.com/');
    }

    protected function getOriginalJobType() {
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        return $jobTypeService->getByName(JobTypeService::FULL_SITE_NAME);
    }

    protected function getOriginalParameters() {
        return 'original-parameters';
    }

    protected function getNewWebsite() {
        $websiteService = $this->container->get('simplytestable.services.websiteservice');

        return $websiteService->fetch('http://new.example.com/');
    }

    protected function getNewJobType() {
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        return $jobTypeService->getByName(JobTypeService::SINGLE_URL_NAME);
    }

    protected function getNewParameters() {
        return 'new-parameters';
    }

    protected function getNewTaskConfigurationCollection()
    {
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $taskConfigurationCollection = new TaskConfigurationCollection();

        foreach ($this->taskTypeOptionsSet as $taskTypeName => $taskTypeOptions) {
            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType(
                $taskTypeService->getByName($taskTypeName)
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