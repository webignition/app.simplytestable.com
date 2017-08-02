<?php
namespace SimplyTestable\ApiBundle\Services\QueryBuilderFactory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Model\JobList\Configuration;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;

class JobListQueryBuilderFactory
{
    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @param TeamService $teamService
     * @param EntityManager $entityManager
     */
    public function __construct(TeamService $teamService, EntityManager $entityManager)
    {
        $this->teamService = $teamService;
        $this->jobRepository = $entityManager->getRepository(Job::class);
    }

    /**
     * @param Configuration $configuration
     *
     * @return QueryBuilder
     */
    public function create(Configuration $configuration)
    {
        $queryBuilder = $this->jobRepository->createQueryBuilder('Job');
        $queryBuilder->where('1 = 1');

        $user = $configuration->getUser();
        $typesToExclude = $configuration->getTypesToExclude();
        $statesToExclude = $configuration->getStatesToExclude();
        $jobIdsToInclude = $configuration->getJobIdsToInclude();
        $jobIdsToExclude = $configuration->getJobIdsToExclude();
        $urlFilter = $configuration->getUrlFilter();

        if (!empty($user)) {
            $this->setUserFilter($queryBuilder, $user);
        }

        if (!empty($typesToExclude)) {
            $this->setTypeExclusion($queryBuilder, $typesToExclude);
        }

        if (!empty($statesToExclude)) {
            $this->setStateExclusion($queryBuilder, $statesToExclude);
        }

        if (!empty($jobIdsToInclude)) {
            $this->setIdsToInclude($queryBuilder, $jobIdsToInclude);
        }

        if (!empty($jobIdsToExclude)) {
            $this->setIdsToExclude($queryBuilder, $jobIdsToExclude);
        }

        if (!empty($urlFilter)) {
            $this->setUrlFilter($queryBuilder, $urlFilter);
        }

        $queryBuilder->orderBy('Job.id', 'DESC');

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param User $user
     */
    private function setUserFilter(QueryBuilder $queryBuilder, User $user)
    {
        $queryUsers = ($this->teamService->hasForUser($user))
            ? $this->teamService->getPeople($this->teamService->getForUser($user))
            : [$user];

        $userWhereParts = [];

        foreach ($queryUsers as $userIndex => $queryUser) {
            $userWhereParts[] = 'Job.user = :User' . $userIndex;
            $queryBuilder->setParameter('User' .  $userIndex, $queryUser);
        }

        $queryBuilder->andWhere(implode(' OR ', $userWhereParts));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param JobType[] $typesToExclude
     */
    private function setTypeExclusion(QueryBuilder $queryBuilder, $typesToExclude)
    {
        $typeExclusionParts = [];

        foreach ($typesToExclude as $typeIndex => $type) {
            $typeExclusionParts[] = 'Job.type != :Type' .  $typeIndex;
            $queryBuilder->setParameter('Type' .  $typeIndex, $type);
        }

        $queryBuilder->andWhere('('.implode(' AND ', $typeExclusionParts).')');
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param State[] $statesToExclude
     */
    private function setStateExclusion(QueryBuilder $queryBuilder, $statesToExclude)
    {
        $stateExclusionParts = [];

        foreach ($statesToExclude as $stateIndex => $state) {
            $stateExclusionParts[] = 'Job.state != :State' .  $stateIndex;
            $queryBuilder->setParameter('State' .  $stateIndex, $state);
        }

        $queryBuilder->andWhere('('.implode(' AND ', $stateExclusionParts).')');
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int[] $idsToInclude
     */
    private function setIdsToInclude(QueryBuilder $queryBuilder, $idsToInclude)
    {
        $idWhereParts = [];

        foreach ($idsToInclude as $idIndex => $id) {
            $idWhereParts[] = 'Job.id = :Id' . $idIndex;
            $queryBuilder->setParameter('Id' .  $idIndex, $id);
        }

        $queryBuilder->orWhere(implode(' OR ', $idWhereParts));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int[] $idsToExclude
     */
    private function setIdsToExclude(QueryBuilder $queryBuilder, $idsToExclude)
    {
        $idWhereParts = [];

        foreach ($idsToExclude as $idIndex => $id) {
            $idWhereParts[] = 'Job.id != :Id' . $idIndex;
            $queryBuilder->setParameter('Id' .  $idIndex, $id);
        }

        $queryBuilder->andWhere(implode(' AND ', $idWhereParts));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $urlFilter
     */
    private function setUrlFilter(QueryBuilder $queryBuilder, $urlFilter)
    {
        $queryBuilder->join('Job.website', 'Website');

        if (substr_count($urlFilter, '*')) {
            $queryBuilder->andWhere('Website.canonicalUrl LIKE :Website');
            $queryBuilder->setParameter('Website', str_replace('*', '%', $urlFilter));
        } else {
            $queryBuilder->andWhere('Website.canonicalUrl = :Website');
            $queryBuilder->setParameter('Website', $urlFilter);
        }
    }
}
