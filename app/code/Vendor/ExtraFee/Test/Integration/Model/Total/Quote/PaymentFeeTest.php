<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Integration\Model\Total\Quote;

use Magento\Customer\Model\Group;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 */
class PaymentFeeTest extends TestCase
{
    /**
     * @magentoConfigFixture current_store extra_fee/payment_fee/enabled 1
     * @magentoConfigFixture current_store extra_fee/payment_fee/amount 2.5
     * @magentoConfigFixture current_store extra_fee/payment_fee/customer_groups 0
     * @magentoConfigFixture current_store extra_fee/payment_fee/payment_methods checkmo
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testFeeIsAppliedForConfiguredGroupAndMethod(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var Quote $quote */
        $quote = $objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        $quote->setTotalsCollectedFlag(false);

        /** @var TotalsCollector $totalsCollector */
        $totalsCollector = $objectManager->create(TotalsCollector::class);
        $total = $totalsCollector->collectAddressTotals($quote, $quote->getShippingAddress());

        $this->assertEquals(2.5, $total->getTotalAmount('extra_fee_payment'));
        $this->assertEquals(2.5, $quote->getShippingAddress()->getExtraFeePaymentAmount());
    }

    /**
     * @magentoConfigFixture current_store extra_fee/payment_fee/enabled 1
     * @magentoConfigFixture current_store extra_fee/payment_fee/amount 2.5
     * @magentoConfigFixture current_store extra_fee/payment_fee/customer_groups 0
     * @magentoConfigFixture current_store extra_fee/payment_fee/payment_methods banktransfer
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testFeeIsNotAppliedWhenPaymentMethodNotConfigured(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var Quote $quote */
        $quote = $objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        $quote->setTotalsCollectedFlag(false);

        /** @var TotalsCollector $totalsCollector */
        $totalsCollector = $objectManager->create(TotalsCollector::class);
        $total = $totalsCollector->collectAddressTotals($quote, $quote->getShippingAddress());

        $this->assertEquals(0.0, (float) $total->getTotalAmount('extra_fee_payment'));
    }
}
