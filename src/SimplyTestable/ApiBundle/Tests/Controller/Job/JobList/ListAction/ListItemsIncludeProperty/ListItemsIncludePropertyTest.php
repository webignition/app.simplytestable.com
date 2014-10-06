<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ListItemsIncludeProperty;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ListContentTest;

abstract class ListItemsIncludePropertyTest extends ListContentTest {
    
    private function getPropertyName() {
        $classNameParts = explode('\\', get_class($this));        
        $localClassName = $classNameParts[count($classNameParts) - 1];
        
        $inflector = \ICanBoogie\Inflector::get();
        return $inflector->underscore(str_replace('Test', '', $localClassName));
    }

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }
    
    abstract protected function getExpectedPropertyValue();

    protected function createJobs() {
        $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'single url'));
    }
    
    protected function getCanonicalUrls() {
        return array(
            self::DEFAULT_CANONICAL_URL
        );
    }

    protected function getExpectedJobListUrls() {
        return $this->getCanonicalUrls();
    }

    protected function getExpectedListLength() {
        return 1;
    }

    protected function getQueryParameters() {
        return array();
    }
    
    public function testListItemsIncludeProperty() {
        $this->assertTrue(property_exists($this->list->jobs[0], $this->getPropertyName()), 'List items lack property "' . $this->getPropertyName() . '"');
    }
    
    public function testPropertyValue() {
        $this->assertEquals($this->getExpectedPropertyValue(), $this->list->jobs[0]->{$this->getPropertyName()});
    }    

}


