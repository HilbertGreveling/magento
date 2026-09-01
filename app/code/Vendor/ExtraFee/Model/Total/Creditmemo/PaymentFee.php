<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Refunds the outstanding portion of the order's extra fee (full amount, not prorated,
 * since the fee is a flat one-time charge rather than a per-item amount).
 */
class PaymentFee extends AbstractTotal
{
    /**
     * Collect the fee total for the credit memo.
     *
     * @param Creditmemo $creditmemo
     * @return $this
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $amount = (float) $order->getExtraFeePaymentAmount();
        $baseAmount = (float) $order->getBaseExtraFeePaymentAmount();

        $refunded = 0.0;
        $baseRefunded = 0.0;
        foreach ($order->getCreditmemosCollection() as $previousCreditmemo) {
            if (!$previousCreditmemo->isCanceled()) {
                $refunded += (float) $previousCreditmemo->getExtraFeePaymentAmount();
                $baseRefunded += (float) $previousCreditmemo->getBaseExtraFeePaymentAmount();
            }
        }

        $allowedAmount = $amount - $refunded;
        $baseAllowedAmount = $baseAmount - $baseRefunded;
        if ($allowedAmount <= 0) {
            return $this;
        }

        $creditmemo->setExtraFeePaymentAmount($allowedAmount);
        $creditmemo->setBaseExtraFeePaymentAmount($baseAllowedAmount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $allowedAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseAllowedAmount);

        return $this;
    }
}
