<?php

namespace App\Model\Stripe\Invoice;

use webignition\Model\Stripe\Invoice\Invoice as BaseInvoice;

class Invoice extends BaseInvoice {

    /**
     *
     * @return array
     */
    public function getLinesSummary() {
        $linesSummary = array();

        foreach ($this->getLines()->getItems() as $line) {
            /* @var $line \webignition\Model\Stripe\Invoice\LineItem\LineItem */
            $linesSummary[] = array(
                'proration' => (int)$line->getIsProrated(),
                'plan_name' => $line->getPlan()->getName(),
                'period_start' => $line->getPeriod()->getStart(),
                'period_end' => $line->getPeriod()->getEnd(),
                'amount' => $line->getAmount()
            );
        }

        return $linesSummary;
    }

}