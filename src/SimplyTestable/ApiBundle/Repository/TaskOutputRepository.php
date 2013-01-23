<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TaskOutputRepository extends EntityRepository
{
    
    public function findOutputByhash($hash) {
        $queryBuilder = $this->createQueryBuilder('TaskOutput');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('TaskOutput');
        $queryBuilder->where('TaskOutput.hash = :Hash');
        $queryBuilder->setParameter('Hash', $hash);
        
        $result = $queryBuilder->getQuery()->getResult();
        if (count($result) === 0) {
            return null;
        }
        
        return $result[0];
    } 
    
    
    public function findHashlessOutputIds($limit = null) {
        $queryBuilder = $this->createQueryBuilder('TaskOutput');
        
        if(is_int($limit) && $limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }        

        $queryBuilder->select('TaskOutput.id');
        $queryBuilder->where('TaskOutput.hash IS NULL');
        
        $result = $queryBuilder->getQuery()->getResult();      
        
        if (count($result) === 0) {
            return array();
        }
        
        return $this->getSingleFieldCollectionFromResult($result, 'id');
    }
    
    private function getSingleFieldCollectionFromResult($result, $fieldName) {
        $collection = array();
        
        foreach ($result as $resultItem) {
            $collection[] = $resultItem[$fieldName];
        }
        
        return $collection;
    }
    
    
    public function findDuplicateHashes($limit = null) {
        $queryBuilder = $this->createQueryBuilder('TaskOutput');
        
        if(is_int($limit) && $limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }         
        
        $queryBuilder->select('TaskOutput.id');        
        $queryBuilder->select('TaskOutput.hash'); 
        $queryBuilder->groupBy('TaskOutput.hash');
        $queryBuilder->having('COUNT(TaskOutput.id) > 1');
        $queryBuilder->where('TaskOutput.hash IS NOT NULL');
        
        $result = $queryBuilder->getQuery()->getResult(); 
        
        if (count($result) === 0) {
            return array();
        }
        
        return $this->getSingleFieldCollectionFromResult($result, 'hash');      
    }    
    
    public function findIdsBy($hash) {
        $queryBuilder = $this->createQueryBuilder('TaskOutput');
        $queryBuilder->select('TaskOutput.id');
        $queryBuilder->where('TaskOutput.hash = :Hash');
        $queryBuilder->setParameter('Hash', $hash);
        
        $ids = array();
        
        $result = $queryBuilder->getQuery()->getResult(); 
        
        foreach ($result as $idResult) {
            $ids[] = $idResult['id'];
        }
        
        return $ids;    
    } 
    
    
    public function findIdsNotIn($excludeIds) {
        $queryBuilder = $this->createQueryBuilder('TaskOutput');
        $queryBuilder->select('TaskOutput.id');
        $queryBuilder->where('TaskOutput.id NOT IN ('.  implode(',', $excludeIds).')');
      
        $result = $queryBuilder->getQuery()->getResult(); 
        
        if (count($result) === 0) {
            return array();
        }
        
        $ids = array();
        
        foreach ($result as $taskOutputIdResult) {
            $ids[] = $taskOutputIdResult['id'];        
        }
        
        sort($ids);
        
        return $ids;       
    }    
}