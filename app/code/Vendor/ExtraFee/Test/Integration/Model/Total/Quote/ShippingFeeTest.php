<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Integration\Model\Total\Quote;

use Magento\Customer\Model\Group;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 */
class ShippingFeeTest extends TestCase
{
    /**
     * @magentoConfigFixture current_store extra_fee/shipping_fee/enabled 1
     * @magentoConfigFixture current_store extra_fee/shipping_fee/amount 5
     * @magentoConfigFixture current_store extra_fee/shipping_fee/customer_groups 0
     * @magentoConfigFixture current_store extra_fee/shipping_fee/shipping_methods flatrate_flatrate
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testFeeIsMergedIntoShippingAmountForConfiguredGroupAndMethod(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var Quote $quote */
        $quote = $objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->setTotalsCollectedFlag(false);

        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $carrierPrice = (float) $scopeConfig->getValue('carriers/flatrate/price');

        /** @var TotalsCollector $totalsCollector */
        $totalsCollector = $objectManager->create(TotalsCollector::class);
        $total = $totalsCollector->collectAddressTotals($quote, $quote->getShippingAddress());

        $this->assertEquals($carrierPrice + 5.0, $total->getShippingAmount());
    }

    /**
     * @magentoConfigFixture current_store extra_fee/shipping_fee/enabled 1
     * @magentoConfigFixture current_store extra_fee/shipping_fee/amount 5
     * @magentoConfigFixture current_store extra_fee/shipping_fee/customer_groups 3
     * @magentoConfigFixture current_store extra_fee/shipping_fee/shipping_methods flatrate_flatrate
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testFeeIsNotAppliedWhenCustomerGroupNotConfigured(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var Quote $quote */
        $quote = $objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->setTotalsCollectedFlag(false);

        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $carrierPrice = (float) $scopeConfig->getValue('carriers/flatrate/price');

        /** @var TotalsCollector $totalsCollector */
        $totalsCollector = $objectManager->create(TotalsCollector::class);
        $total = $totalsCollector->collectAddressTotals($quote, $quote->getShippingAddress());

        $this->assertEquals($carrierPrice, $total->getShippingAmount());
    }
}
