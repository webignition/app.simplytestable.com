<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class TaskTypeService
{
    const HTML_VALIDATION_TYPE = 'HTML validation';
    const CSS_VALIDATION_TYPE = 'CSS validation';
    const JS_STATIC_ANALYSIS_TYPE = 'JS static analysis';
    const URL_DISCOVERY_TYPE = 'URL discovery';
    const LINK_INTEGRITY_TYPE = 'Link integrity';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $taskTypeRepository;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->taskTypeRepository = $entityManager->getRepository(TaskType::class);
    }

    /**
     * @param string $taskTypeName
     *
     * @return bool
     */
    public function exists($taskTypeName)
    {
        return !is_null($this->get($taskTypeName));
    }

    /**
     * @param string $name
     *
     * @return TaskType
     */
    public function get($name)
    {
        /* @var TaskType $taskType */
        $taskType = $this->taskTypeRepository->findOneBy([
            'name' => $name
        ]);

        return $taskType;
    }

    /**
     * @return int
     */
    public function getSelectableCount()
    {
        $queryBuilder = $this->taskTypeRepository->createQueryBuilder('TaskType');

        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(TaskType.id) as task_type_total');
        $queryBuilder->where('TaskType.selectable = 1');

        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['task_type_total']);
    }
}
