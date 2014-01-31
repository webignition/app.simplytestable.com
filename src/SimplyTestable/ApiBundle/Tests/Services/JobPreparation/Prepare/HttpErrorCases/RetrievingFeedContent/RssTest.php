<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HttpErrorCases\RetrievingFeedContent;

class RssTest extends FeedTest { 
    
    protected function getFeedLink() {
        return '<link rel="alternate" type="application/rss+xml" title="Title Example" href="http://example.com/rss.xml" />';
    }

}
