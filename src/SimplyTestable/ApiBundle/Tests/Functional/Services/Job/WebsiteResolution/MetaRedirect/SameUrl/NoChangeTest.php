<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

class NoChangeTest extends SameUrlTest {

    protected function getTestHttpFixtures() {
        return $this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . self::SOURCE_URL . "\"></head></html>",
        ));
    }


    public function testNoChange() {}
}
