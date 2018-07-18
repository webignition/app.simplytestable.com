<?php
namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\Type;

class JobTypeService
{
    const DEFAULT_TYPE_ID = 1;
    const FULL_SITE_NAME = 'Full site';
    const SINGLE_URL_NAME = 'Single URL';
    const CRAWL_NAME = 'crawl';

    /**
     * @var EntityRepository
     */
    private $jobTypeRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->jobTypeRepository = $entityManager->getRepository(Type::class);
    }

    /**
     * @return Type
     */
    public function getFullSiteType()
    {
        return $this->get(self::FULL_SITE_NAME);
    }

    /**
     * @return Type
     */
    public function getSingleUrlType()
    {
        return $this->get(self::SINGLE_URL_NAME);
    }

    /**
     * @return Type
     */
    public function getCrawlType()
    {
        return $this->get(self::CRAWL_NAME);
    }

    /**
     * @param $name
     *
     * @return Type
     */
    public function get($name)
    {
        /* @var Type $jobType */
        $jobType = $this->jobTypeRepository->findOneBy([
            'name' => $name,
        ]);

        return $jobType;
    }
}
