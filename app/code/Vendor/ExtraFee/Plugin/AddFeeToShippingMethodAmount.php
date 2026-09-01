<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Plugin;

use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote\Address\Rate;
use Vendor\ExtraFee\Model\FeeCalculator;

/**
 * Shows the shipping method fee already added into the price of matching methods in the
 * shipping method selection list, so it's visible before the customer even reaches the totals.
 */
class AddFeeToShippingMethodAmount
{
    /**
     * @param FeeCalculator $feeCalculator
     */
    public function __construct(private readonly FeeCalculator $feeCalculator)
    {
    }

    /**
     * Add the fee amount to the displayed rate when it applies to the current quote.
     *
     * @param ShippingMethodConverter $subject
     * @param ShippingMethodInterface $result
     * @param Rate $rateModel
     * @param string $quoteCurrencyCode
     * @return ShippingMethodInterface
     */
    public function afterModelToDataObject(
        ShippingMethodConverter $subject,
        ShippingMethodInterface $result,
        $rateModel,
        $quoteCurrencyCode
    ): ShippingMethodInterface {
        $quote = $rateModel->getAddress() ? $rateModel->getAddress()->getQuote() : null;
        if (!$quote) {
            return $result;
        }

        $methodCode = $result->getCarrierCode() . '_' . $result->getMethodCode();
        $fee = $this->feeCalculator->getShippingFeeAmount($quote, $methodCode);
        if ($fee === null) {
            return $result;
        }

        $result->setAmount($result->getAmount() + $fee->amount);
        $result->setBaseAmount($result->getBaseAmount() + $fee->baseAmount);
        $result->setPriceExclTax($result->getPriceExclTax() + $fee->amount);
        $result->setPriceInclTax($result->getPriceInclTax() + $fee->amount);

        return $result;
    }
}
