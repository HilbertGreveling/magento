<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Model;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;
use Vendor\ExtraFee\Helper\Config;

/**
 * Single source of truth for whether/how much of each extra fee applies to a quote.
 * Shared by the quote total collectors (which charge the fee) and the checkout display
 * plugins (which show it before checkout), so the two can never disagree.
 */
class FeeCalculator
{
    /**
     * Constructor.
     *
     * @param Config $config
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        private readonly Config $config,
        private readonly PriceCurrencyInterface $priceCurrency
    ) {
    }

    /**
     * Get the shipping fee amount for the quote, or null if it does not apply.
     *
     * @param Quote $quote
     * @param string|null $shippingMethod
     * @return FeeAmount|null Null when the shipping fee does not apply.
     */
    public function getShippingFeeAmount(Quote $quote, ?string $shippingMethod): ?FeeAmount
    {
        $storeId = (int) $quote->getStore()->getId();

        if (!$this->config->isShippingFeeEnabled($storeId)) {
            return null;
        }

        if (!$this->matchesCustomerGroup($quote, $this->config->getShippingFeeCustomerGroups($storeId))) {
            return null;
        }

        if ($shippingMethod === null
            || !in_array($shippingMethod, $this->config->getShippingFeeMethods($storeId), true)
        ) {
            return null;
        }

        return $this->buildAmount($this->config->getShippingFeeAmount($storeId), $quote);
    }

    /**
     * Get the payment fee amount for the quote, or null if it does not apply.
     *
     * @param Quote $quote
     * @param string|null $paymentMethod
     * @return FeeAmount|null Null when the payment fee does not apply.
     */
    public function getPaymentFeeAmount(Quote $quote, ?string $paymentMethod): ?FeeAmount
    {
        $storeId = (int) $quote->getStore()->getId();

        if (!$this->config->isPaymentFeeEnabled($storeId)) {
            return null;
        }

        if (!$this->matchesCustomerGroup($quote, $this->config->getPaymentFeeCustomerGroups($storeId))) {
            return null;
        }

        if ($paymentMethod === null
            || !in_array($paymentMethod, $this->config->getPaymentFeeMethods($storeId), true)
        ) {
            return null;
        }

        return $this->buildAmount($this->config->getPaymentFeeAmount($storeId), $quote);
    }

    /**
     * Whether the quote's customer group is among the configured groups.
     *
     * @param Quote $quote
     * @param string[] $configuredGroups
     * @return bool
     */
    private function matchesCustomerGroup(Quote $quote, array $configuredGroups): bool
    {
        return in_array((string) $quote->getCustomerGroupId(), $configuredGroups, true);
    }

    /**
     * Build the fee amount object, or null when the configured base amount is not positive.
     *
     * @param float $baseAmount
     * @param Quote $quote
     * @return FeeAmount|null Null when the configured amount is not positive.
     */
    private function buildAmount(float $baseAmount, Quote $quote): ?FeeAmount
    {
        if ($baseAmount <= 0) {
            return null;
        }

        return new FeeAmount($this->priceCurrency->convert($baseAmount, $quote->getStore()), $baseAmount);
    }
}
