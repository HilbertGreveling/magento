<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Block\Sales\Order;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Adds a "Payment Method Fee" row to the order/invoice/credit memo totals block. A single
 * class works for all three because Order, Invoice, and Creditmemo each expose their own
 * getExtraFeePaymentAmount() column.
 */
class PaymentFee extends Template
{
    /**
     * Add the fee row to the parent totals block, if the source has a non-zero fee.
     *
     * @return $this
     */
    public function initTotals()
    {
        $source = $this->getParentBlock()->getSource();
        $amount = (float) $source->getExtraFeePaymentAmount();
        if ($amount <= 0) {
            return $this;
        }

        $this->getParentBlock()->addTotal(
            new DataObject([
                'code' => 'extra_fee_payment',
                'value' => $amount,
                'label' => __('Payment Method Fee'),
            ]),
            'shipping'
        );

        return $this;
    }
}
