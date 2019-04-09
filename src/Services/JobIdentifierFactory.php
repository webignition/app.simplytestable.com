<?php

namespace App\Services;

use App\Entity\Job\Job;
use Hashids\Hashids;

class JobIdentifierFactory
{
    private $hashIdCreator;
    private $instanceId;

    public function __construct(Hashids $hashIdCreator, int $instanceId)
    {
        $this->hashIdCreator = $hashIdCreator;
        $this->instanceId = $instanceId;
    }

    public function create(Job $job): string
    {
        return $this->hashIdCreator->encode([
            $this->instanceId,
            $job->getId(),
        ]);
    }
}
