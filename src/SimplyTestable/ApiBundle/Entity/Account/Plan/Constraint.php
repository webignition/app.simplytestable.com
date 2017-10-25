<?php
namespace SimplyTestable\ApiBundle\Entity\Account\Plan;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="AccountPlanConstraint"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Constraint
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @SerializerAnnotation\Expose
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true, name="limit_threshold")
     * @SerializerAnnotation\Expose
     */
    private $limit = null;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     * @SerializerAnnotation\Expose
     */
    private $isAvailable = true;

    /**
     * @var Plan
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Plan", inversedBy="constraints")
     * @ORM\JoinColumn(name="plan_id", referencedColumnName="id", nullable=false)
     */
    private $plan;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param bool $isAvailable
     */
    public function setIsAvailable($isAvailable)
    {
        $this->isAvailable = $isAvailable;
    }

    /**
     * @return bool
     */
    public function getIsAvailable()
    {
        return $this->isAvailable;
    }

    /**
     * @param Plan $plan
     */
    public function setPlan(Plan $plan)
    {
        $this->plan = $plan;
    }

    /**
     * @return Plan
     */
    public function getPlan()
    {
        return $this->plan;
    }
}
