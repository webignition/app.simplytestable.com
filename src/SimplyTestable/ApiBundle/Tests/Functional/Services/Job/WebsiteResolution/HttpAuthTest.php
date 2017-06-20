<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class HttpAuthTest extends BaseSimplyTestableTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200"
        )));
    }

    public function testJobHttpAuthParametersArePassedToUrlResolver()
    {
        $username = 'example';
        $password = 'password';

        $job = $this->createJobFactory()->create([
            JobFactory::KEY_PARAMETERS => [
                'http-auth-username' => $username,
                'http-auth-password' => $password,
            ],
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->resolveJob($job->getWebsite()->getCanonicalUrl(), $job->getId());

        /* @var $urlResolverBaseRequestCurlOptions \Guzzle\Common\Collection */
        $urlResolverBaseRequestCurlOptions =
            $this
                ->getJobWebsiteResolutionService()
                ->getUrlResolver($job)
                ->getConfiguration()
                ->getBaseRequest()
                ->getCurlOptions();

        $this->assertTrue($urlResolverBaseRequestCurlOptions->hasKey(CURLOPT_USERPWD));
        $this->assertEquals($username . ':' . $password, $urlResolverBaseRequestCurlOptions->get(CURLOPT_USERPWD));
    }
}
