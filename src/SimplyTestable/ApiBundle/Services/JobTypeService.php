<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Type;

class JobTypeService extends EntityService
{
    const DEFAULT_TYPE_ID = 1;
    const FULL_SITE_NAME = 'Full site';
    const SINGLE_URL_NAME = 'Single URL';
    const CRAWL_NAME = 'crawl';

    /**
     * {@inheritdoc}
     */
    protected function getEntityName()
    {
        return Type::class;
    }

    /**
     * @param string $name
     *
     * @return Type
     */
    public function getByName($name)
    {
        /* @var Type $type */
        $type = $this->getEntityRepository()->findOneBy([
            'name' => $name
        ]);

        return $type;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return !is_null($this->getByName($name));
    }
}
