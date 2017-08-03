<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class TaskTypeService extends EntityService
{
    const HTML_VALIDATION_TYPE = 'HTML validation';
    const CSS_VALIDATION_TYPE = 'CSS validation';
    const JS_STATIC_ANALYSIS_TYPE = 'JS static analysis';
    const URL_DISCOVERY_TYPE = 'URL discovery';
    const LINK_INTEGRITY_TYPE = 'Link integrity';

    /**
     * {@inheritdoc}
     */
    protected function getEntityName()
    {
        return TaskType::class;
    }

    /**
     * @param string $taskTypeName
     *
     * @return bool
     */
    public function exists($taskTypeName)
    {
        return !is_null($this->getByName($taskTypeName));
    }

    /**
     * @param string $taskTypeName
     *
     * @return TaskType
     */
    public function getByName($taskTypeName)
    {
        /* @var TaskType $taskType */
        $taskType = $this->getEntityRepository()->findOneBy([
            'name' => $taskTypeName
        ]);

        return $taskType;
    }

    /**
     * @return int
     */
    public function getSelectableCount()
    {
        $queryBuilder = $this->getEntityRepository()->createQueryBuilder('TaskType');

        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(TaskType.id) as task_type_total');
        $queryBuilder->where('TaskType.selectable = 1');

        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['task_type_total']);
    }
}
