<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution\MetaRedirect\SameUrl;

use SimplyTestable\ApiBundle\Tests\Factory\HtmlDocumentFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;

class InvalidContentTypeTest extends SameUrlTest
{

    protected function getTestHttpFixtures()
    {
        return [
            HttpFixtureFactory::createSuccessResponse(
                'text/plain',
                HtmlDocumentFactory::createMetaRedirectDocument('http://foo.example.com')
            ),
        ];
    }

    public function testWithInvalidContentType()
    {
    }
}
