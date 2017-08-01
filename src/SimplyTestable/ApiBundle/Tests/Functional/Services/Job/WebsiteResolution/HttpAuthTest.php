<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\WebsiteResolution;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class HttpAuthTest extends BaseSimplyTestableTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
        ]);
    }

    public function testJobHttpAuthParametersArePassedToUrlResolver()
    {
        $username = 'example';
        $password = 'password';

        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create([
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
