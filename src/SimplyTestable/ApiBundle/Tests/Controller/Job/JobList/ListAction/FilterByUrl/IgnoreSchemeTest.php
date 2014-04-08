<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\FilterByUrl;

class IgnoreSchemeTest extends FilterByUrlTest {
   
    protected function getExpectedListLength() {
        return 2;
    }

    protected function getFilter() {
        return '*://example.com/';
    }
    
    protected function getExpectedJobListUrls() {
        return array(            
            'https://example.com/',
            'http://example.com/',            
        );
    }    

}


