<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\Prepare\HttpErrorCases\RetrievingFeedContent;

class AtomTest extends FeedTest {

    protected function getFeedLink() {
        return '<link rel="alternate" type="application/atom+xml" title="Title Example" href="http://example.com/atom.xml" />';
    }
}
