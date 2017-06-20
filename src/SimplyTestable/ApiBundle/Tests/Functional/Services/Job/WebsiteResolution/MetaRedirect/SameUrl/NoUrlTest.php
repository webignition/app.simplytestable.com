<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

class NoUrlTest extends SameUrlTest {

    protected function getTestHttpFixtures() {
        return $this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0\"></head></html>",
        ));
    }


    public function testWithNoUrl() {}
//
//
//    public function testWithNoUrl() {
//        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
//            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0\"></head></html>",
//        )));
//
////        $resolver = new Resolver();
////        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
////
////        $this->assertEquals(self::SOURCE_URL, $resolver->resolve(self::SOURCE_URL));
//
////        $this->getJobWebsiteResolutionService()->resolve($this->job);
////        $this->assertEquals(self::SOURCE_URL, $this->job->getWebsite()->getCanonicalUrl());
//    }
//
//
//    public function testWithSameUrl() {
//        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
//            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . self::SOURCE_URL . "\"></head></html>",
//        )));
//
////        $resolver = new Resolver();
////        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
////
////        $this->assertEquals(self::SOURCE_URL, $resolver->resolve(self::SOURCE_URL));
//
////        $this->getJobWebsiteResolutionService()->resolve($this->job);
////        $this->assertEquals(self::SOURCE_URL, $this->job->getWebsite()->getCanonicalUrl());
//    }

//    public function testWithDifferentUrl() {
//        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
//            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=" . self::EFFECTIVE_URL . "\"></head></html>",
//            "HTTP/1.0 200 OK"
//        )));
//
//        $resolver = new Resolver();
//        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
//
//        $this->assertEquals(self::EFFECTIVE_URL, $resolver->resolve(self::SOURCE_URL));
//    }
//
//    public function testWithRelativeUrl() {
//        $relativeUrl = 'foo/bar.html';
//
//        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
//            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=".$relativeUrl."\"></head></html>",
//            "HTTP/1.0 200 OK"
//        )));
//
//        $resolver = new Resolver();
//        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
//
//        $this->assertEquals(self::SOURCE_URL . $relativeUrl, $resolver->resolve(self::SOURCE_URL));
//    }
//
//    public function testWithProtocolRelativeUrl() {
//        $url = '//example.com/foo/bar.html';
//
//        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
//            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n<!DOCTYPE html><html><head><meta http-equiv=\"refresh\" content=\"0; url=".$url."\"></head></html>",
//            "HTTP/1.0 200 OK"
//        )));
//
//        $resolver = new Resolver();
//        $resolver->getConfiguration()->setBaseRequest($this->getHttpClient()->get());
//
//        $this->assertEquals('http:' . $url, $resolver->resolve(self::SOURCE_URL));
//    }
}
