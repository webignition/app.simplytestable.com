<?php
namespace SimplyTestable\ApiBundle\Entity\Account\Plan;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="AccountPlan"
 * )
 */
class Plan implements \JsonSerializable
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
     * @ORM\Column(type="string", unique=true)
     */
    private $name;

    /**
     * @var DoctrineCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint",
     *     mappedBy="plan",
     *     cascade={"persist", "remove"}
     * )
     */
    private $constraints;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isPremium = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $isVisible = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $stripe_id = null;

    public function __construct()
    {
        $this->constraints = new ArrayCollection();
        $this->isVisible = false;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Plan
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param bool $isVisible
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;
    }

    /**
     * @return bool
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }

    /**
     * @param Constraint $constraint
     */
    public function addConstraint(Constraint $constraint)
    {
        $this->constraints[] = $constraint;
        $constraint->setPlan($this);
    }

    /**
     * @param Constraint $constraint
     */
    public function removeConstraint(Constraint $constraint)
    {
        $this->constraints->removeElement($constraint);
    }

    /**
     * @return DoctrineCollection
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * @param string $constraintName
     *
     * @return Constraint|null
     */
    public function getConstraintNamed($constraintName)
    {
        foreach ($this->getConstraints() as $constraint) {
            if ($constraint->getName() === $constraintName) {
                return $constraint;
            }
        }

        return null;
    }

    /**
     * @param bool $isPremium
     */
    public function setIsPremium($isPremium)
    {
        $this->isPremium = $isPremium;
    }

    /**
     * @return bool
     */
    public function getIsPremium()
    {
        return $this->isPremium;
    }

    /**
     * @param string $stripeId
     */
    public function setStripeId($stripeId)
    {
        $this->stripe_id = $stripeId;
    }

    /**
     * @return string
     */
    public function getStripeId()
    {
        return $this->stripe_id;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'is_premium' => $this->isPremium,
        ];
    }
}
