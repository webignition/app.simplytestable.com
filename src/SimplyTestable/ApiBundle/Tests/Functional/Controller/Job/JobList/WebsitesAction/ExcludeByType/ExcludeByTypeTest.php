<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\ExcludeByType;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\ContentTest;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ExcludeByTypeTest extends ContentTest
{
    /**
     * @var string[]
     */
    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'
    );

    private $excludedTypeName = 'crawl';

    protected function getRequestingUser()
    {
        return $this->getUserService()->getPublicUser();
    }

    protected function createJobs()
    {
        $jobFactory = new JobFactory($this->container);

        foreach ($this->canonicalUrls as $canonicalUrl) {
            $this->jobs[] = $jobFactory->createResolveAndPrepare([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
                JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            ]);
        }
    }

    protected function applyPreListChanges()
    {
        $this->jobs[2]->setType($this->getJobTypeService()->getByName($this->excludedTypeName));
        $this->getJobService()->persistAndFlush($this->jobs[2]);
    }

    protected function getCanonicalUrls()
    {
        return $this->canonicalUrls;
    }

    protected function getExpectedWebsitesList()
    {
        return array_slice($this->getCanonicalUrls(), 0, 2);
    }

    protected function getQueryParameters()
    {
        return array(
            'exclude-types' => array(
                $this->excludedTypeName
            )
        );
    }
}
