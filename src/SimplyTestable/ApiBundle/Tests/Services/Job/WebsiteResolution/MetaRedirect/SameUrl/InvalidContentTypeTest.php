<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

class InvalidContentTypeTest extends SameUrlTest {
    
    protected function getTestHttpFixtures() {
        return $this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/plain\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=http://foo.example.com></head></html>",
        ));
    }
    

    public function testWithInvalidContentType() {}
}
