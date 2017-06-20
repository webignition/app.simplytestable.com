<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd\WithDiscount;

use SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionTrialWillEnd\WithDiscountTest;

class WithoutCardTest extends WithDiscountTest {

    protected function getHasCard() {
        return false;
    }


}
