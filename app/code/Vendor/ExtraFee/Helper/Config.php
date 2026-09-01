<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    private const XML_PATH_SHIPPING_FEE_ENABLED = 'extra_fee/shipping_fee/enabled';
    private const XML_PATH_SHIPPING_FEE_AMOUNT = 'extra_fee/shipping_fee/amount';
    private const XML_PATH_SHIPPING_FEE_CUSTOMER_GROUPS = 'extra_fee/shipping_fee/customer_groups';
    private const XML_PATH_SHIPPING_FEE_METHODS = 'extra_fee/shipping_fee/shipping_methods';

    private const XML_PATH_PAYMENT_FEE_ENABLED = 'extra_fee/payment_fee/enabled';
    private const XML_PATH_PAYMENT_FEE_AMOUNT = 'extra_fee/payment_fee/amount';
    private const XML_PATH_PAYMENT_FEE_CUSTOMER_GROUPS = 'extra_fee/payment_fee/customer_groups';
    private const XML_PATH_PAYMENT_FEE_METHODS = 'extra_fee/payment_fee/payment_methods';

    /**
     * Whether the shipping method fee is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isShippingFeeEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHIPPING_FEE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the configured shipping method fee amount. Never negative.
     *
     * @param int|null $storeId
     * @return float
     */
    public function getShippingFeeAmount(?int $storeId = null): float
    {
        return max(0.0, (float) $this->scopeConfig->getValue(
            self::XML_PATH_SHIPPING_FEE_AMOUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get the customer group IDs the shipping method fee applies to.
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getShippingFeeCustomerGroups(?int $storeId = null): array
    {
        return $this->explodeConfigValue(self::XML_PATH_SHIPPING_FEE_CUSTOMER_GROUPS, $storeId);
    }

    /**
     * Get the shipping method codes the fee applies to.
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getShippingFeeMethods(?int $storeId = null): array
    {
        return $this->explodeConfigValue(self::XML_PATH_SHIPPING_FEE_METHODS, $storeId);
    }

    /**
     * Whether the payment method fee is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isPaymentFeeEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAYMENT_FEE_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the configured payment method fee amount. Never negative.
     *
     * @param int|null $storeId
     * @return float
     */
    public function getPaymentFeeAmount(?int $storeId = null): float
    {
        return max(0.0, (float) $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT_FEE_AMOUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Get the customer group IDs the payment method fee applies to.
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getPaymentFeeCustomerGroups(?int $storeId = null): array
    {
        return $this->explodeConfigValue(self::XML_PATH_PAYMENT_FEE_CUSTOMER_GROUPS, $storeId);
    }

    /**
     * Get the payment method codes the fee applies to.
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getPaymentFeeMethods(?int $storeId = null): array
    {
        return $this->explodeConfigValue(self::XML_PATH_PAYMENT_FEE_METHODS, $storeId);
    }

    /**
     * Split a stored comma-separated multiselect config value into an array.
     *
     * @param string $path
     * @param int|null $storeId
     * @return string[]
     */
    private function explodeConfigValue(string $path, ?int $storeId): array
    {
        $value = (string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);

        return $value === '' ? [] : explode(',', $value);
    }
}
