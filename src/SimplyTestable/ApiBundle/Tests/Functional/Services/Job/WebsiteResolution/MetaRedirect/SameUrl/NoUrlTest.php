<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

use SimplyTestable\ApiBundle\Tests\Factory\HtmlDocumentFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;

class NoUrlTest extends SameUrlTest
{
    protected function getTestHttpFixtures()
    {
        return [
            HttpFixtureFactory::createSuccessResponse(
                'text/html',
                HtmlDocumentFactory::createMetaRedirectDocument(null)
            ),
        ];
    }

    public function testWithNoUrl()
    {
    }
}
