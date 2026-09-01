<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Plugin;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Model\Quote\Payment;
use Vendor\ExtraFee\Model\FeeCalculator;

/**
 * Appends "(+$X.XX)" to the title of payment methods that trigger the payment fee, so the
 * cost is visible in the payment method selection list before the customer picks one.
 */
class AppendPaymentFeeToTitle
{
    /**
     * @param FeeCalculator $feeCalculator
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly FeeCalculator $feeCalculator,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * Append the fee amount to the title when it applies to the current quote.
     *
     * @param AbstractMethod $subject
     * @param string $result
     * @return string
     */
    public function afterGetTitle(AbstractMethod $subject, $result)
    {
        // getInfoInstance() throws if info_instance isn't set yet (checkout's initial method list build)
        $payment = $subject->getData('info_instance');

        if (!$payment instanceof Payment) {
            return $result;
        }

        $quote = $payment->getQuote();
        if (!$quote) {
            return $result;
        }

        $fee = $this->feeCalculator->getPaymentFeeAmount($quote, $subject->getCode());
        if ($fee === null) {
            return $result;
        }

        return $result . ' (+' . $this->priceCurrency->format($fee->amount, false) . ')';
    }
}
