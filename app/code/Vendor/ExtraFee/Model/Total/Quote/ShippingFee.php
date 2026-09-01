<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Model\Total\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Vendor\ExtraFee\Model\FeeCalculator;

/**
 * Adds the shipping method fee directly onto the existing "shipping" total, so it shows as
 * part of the Shipping line instead of a separate row, and is carried through order/invoice/
 * credit memo lifecycle "for free" via Magento's native shipping_amount handling.
 */
class ShippingFee extends AbstractTotal
{
    /**
     * @param FeeCalculator $feeCalculator
     */
    public function __construct(private readonly FeeCalculator $feeCalculator)
    {
    }

    /**
     * Add the fee amount onto the "shipping" total when it applies.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        $shippingMethod = $shippingAssignment->getShipping()->getAddress()->getShippingMethod();
        $fee = $this->feeCalculator->getShippingFeeAmount($quote, $shippingMethod ?: null);
        if ($fee === null) {
            return $this;
        }

        // Merges into the existing "shipping" total amount rather than adding a new total code.
        $total->addTotalAmount('shipping', $fee->amount);
        $total->addBaseTotalAmount('shipping', $fee->baseAmount);

        return $this;
    }

    /**
     * No separate totals line; the fee is folded into the "shipping" row.
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        return null;
    }
}
