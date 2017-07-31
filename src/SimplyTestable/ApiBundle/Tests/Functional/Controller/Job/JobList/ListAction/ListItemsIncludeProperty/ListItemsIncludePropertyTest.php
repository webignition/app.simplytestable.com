<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ListItemsIncludeProperty;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ListContentTest;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class ListItemsIncludePropertyTest extends ListContentTest
{
    private function getPropertyName()
    {
        $classNameParts = explode('\\', get_class($this));
        $localClassName = $classNameParts[count($classNameParts) - 1];

        $inflector = \ICanBoogie\Inflector::get();
        return $inflector->underscore(str_replace('Test', '', $localClassName));
    }

    protected function getRequestingUser()
    {
        return $this->getUserService()->getPublicUser();
    }

    abstract protected function getExpectedPropertyValue();

    protected function createJobs()
    {
        $jobFactory = new JobFactory($this->container);

        $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);
    }

    protected function getCanonicalUrls()
    {
        return array(
            self::DEFAULT_CANONICAL_URL
        );
    }

    protected function getExpectedJobListUrls()
    {
        return $this->getCanonicalUrls();
    }

    protected function getExpectedListLength()
    {
        return 1;
    }

    protected function getQueryParameters()
    {
        return array();
    }

    public function testListItemsIncludeProperty()
    {
        $this->assertTrue(
            property_exists($this->list->jobs[0], $this->getPropertyName()),
            'List items lack property "' . $this->getPropertyName() . '"'
        );
    }

    public function testPropertyValue()
    {
        $this->assertEquals($this->getExpectedPropertyValue(), $this->list->jobs[0]->{$this->getPropertyName()});
    }
}
