<?php

namespace SimplyTestable\ApiBundle\Model\Job\Summary;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class CrawlSummary implements \JsonSerializable
{
    /**
     * @var Job
     */
    private $crawlJob;

    /**
     * @var int
     */
    private $processedUrlCount;

    /**
     * @var int
     */
    private $discoveredUrlCount;

    /**
     * @var int
     */
    private $limit;

    /**
     * @param Job $crawlJob
     * @param int $processedUrlCount
     * @param int $discoveredUrlCount
     * @param int $limit
     */
    public function __construct(
        Job $crawlJob,
        $processedUrlCount,
        $discoveredUrlCount,
        $limit
    ) {
        $this->crawlJob = $crawlJob;
        $this->processedUrlCount = $processedUrlCount;
        $this->discoveredUrlCount = $discoveredUrlCount;
        $this->limit = $limit;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $serializedCrawlJob = [
            'id' => $this->crawlJob->getId(),
            'state' => str_replace('job-', '', $this->crawlJob->getState()->getName()),
            'processed_url_count' => $this->processedUrlCount,
            'discovered_url_count' => $this->discoveredUrlCount,
        ];

        if (!empty($this->limit)) {
            $serializedCrawlJob['limit'] = $this->limit;
        }

        return $serializedCrawlJob;
    }
}
