<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Applies the order's extra fee to the first (non-cancelled) invoice only.
 */
class PaymentFee extends AbstractTotal
{
    /**
     * Collect the fee total for the invoice.
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $amount = (float) $invoice->getOrder()->getExtraFeePaymentAmount();
        $baseAmount = (float) $invoice->getOrder()->getBaseExtraFeePaymentAmount();

        if (!$amount) {
            return $this;
        }

        foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
            if ($previousInvoice->getExtraFeePaymentAmount() !== null && !$previousInvoice->isCanceled()) {
                return $this;
            }
        }

        $invoice->setExtraFeePaymentAmount($amount);
        $invoice->setBaseExtraFeePaymentAmount($baseAmount);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseAmount);

        return $this;
    }
}
