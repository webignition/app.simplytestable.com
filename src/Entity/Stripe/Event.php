<?php
namespace App\Entity\Stripe;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use webignition\Model\Stripe\Event\Event as StripeEventModel;
use webignition\Model\Stripe\Event\Factory as StripeEventFactory;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="StripeEvent",
 *     indexes={
 *         @ORM\Index(name="type_idx", columns={"type"})
 *     }
 * )
 */
class Event implements \JsonSerializable
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected $stripeId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $isLive;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true, name="data")
     */
    protected $stripeEventData;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    protected $user;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $isProcessed = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $stripeId
     */
    public function setStripeId($stripeId)
    {
        $this->stripeId = $stripeId;
    }

    /**
     * @return string
     */
    public function getStripeId()
    {
        return $this->stripeId;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param boolean $isLive
     */
    public function setIsLive($isLive)
    {
        $this->isLive = $isLive;
    }

    /**
     * @return bool
     */
    public function getIsLive()
    {
        return $this->isLive;
    }

    /**
     * @param string $stripeEventData
     */
    public function setStripeEventData($stripeEventData)
    {
        $this->stripeEventData = $stripeEventData;
    }

    /**
     * @return string
     */
    public function getStripeEventData()
    {
        return $this->stripeEventData;
    }

    /**
     * @return StripeEventModel
     */
    public function getStripeEventObject()
    {
        $stripeEventData = $this->getStripeEventData();

        if (empty($stripeEventData)) {
            return null;
        }

        /* @var StripeEventModel $model */
        $model = StripeEventFactory::create($stripeEventData);

        return $model;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param boolean $isProcessed
     */
    public function setIsProcessed($isProcessed)
    {
        $this->isProcessed = $isProcessed;
    }

    /**
     * @return bool
     */
    public function getIsProcessed()
    {
        return $this->isProcessed;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $eventData = [
            'stripe_id' => $this->getStripeId(),
            'type' => $this->getType(),
            'is_live' => $this->getIsLive(),
            'stripe_event_data' => $this->getStripeEventData(),
            'is_processed' => $this->getIsProcessed(),
        ];

        $user = $this->getUser();

        if (!empty($user)) {
            $eventData['user'] = $this->getUser()->getEmail();
        }

        return $eventData;
    }
}
