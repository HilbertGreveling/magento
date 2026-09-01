<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Model\Total\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Vendor\ExtraFee\Model\FeeAmount;
use Vendor\ExtraFee\Model\FeeCalculator;

/**
 * Adds a standalone "Payment Method Fee" total line, applied once per shipping address.
 *
 * Mirrors Magento's own multishipping behavior: "Ship to Multiple Addresses" creates one
 * order per shipping address, each with its own independently charged shipping amount
 * (see Magento\Multishipping\Model\Checkout\Type\Multishipping::_prepareOrder()). Charging
 * this fee once per address keeps it consistent with that per-order charging model.
 */
class PaymentFee extends AbstractTotal
{
    private const CODE = 'extra_fee_payment';

    /**
     * Constructor.
     *
     * @param FeeCalculator $feeCalculator
     */
    public function __construct(private readonly FeeCalculator $feeCalculator)
    {
        $this->setCode(self::CODE);
    }

    /**
     * Add the fee amount to the quote total when it applies to this quote.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        $fee = $this->calculate($quote);
        if ($fee === null) {
            return $this;
        }

        $this->_setAmount($fee->amount);
        $this->_setBaseAmount($fee->baseAmount);

        return $this;
    }

    /**
     * Return the fee as a checkout/cart totals line, or null when no fee applies.
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $fee = $this->calculate($quote);
        if ($fee === null) {
            return null;
        }

        return [
            'code' => self::CODE,
            'title' => __('Payment Method Fee'),
            'value' => $fee->amount,
        ];
    }

    /**
     * Compute the fee amount for the quote's currently selected payment method.
     *
     * @param Quote $quote
     * @return FeeAmount|null
     */
    private function calculate(Quote $quote): ?FeeAmount
    {
        $payment = $quote->getPayment();

        return $this->feeCalculator->getPaymentFeeAmount($quote, $payment !== null ? $payment->getMethod() : null);
    }
}
