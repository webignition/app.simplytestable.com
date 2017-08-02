<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

abstract class EntityService
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return string
     */
    abstract protected function getEntityName();

    /**
     * @return EntityManager
     */
    public function getManager()
    {
        return $this->entityManager;
    }

    /**
     * @return EntityRepository
     */
    public function getEntityRepository()
    {
        if (is_null($this->entityRepository)) {
            $this->entityRepository = $this->entityManager->getRepository($this->getEntityName());
        }

        return $this->entityRepository;
    }
}
