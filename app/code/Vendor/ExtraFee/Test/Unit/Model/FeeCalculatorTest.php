<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Model;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Helper\Config;
use Vendor\ExtraFee\Model\FeeCalculator;

class FeeCalculatorTest extends TestCase
{
    private const STORE_ID = 1;

    /** @var Config|MockObject */
    private $config;

    /** @var PriceCurrencyInterface|MockObject */
    private $priceCurrency;

    /** @var FeeCalculator */
    private FeeCalculator $feeCalculator;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $this->priceCurrency->method('convert')->willReturnArgument(0);

        $this->feeCalculator = new FeeCalculator($this->config, $this->priceCurrency);
    }

    public function testShippingFeeAppliesWhenEnabledGroupAndMethodMatch(): void
    {
        $this->config->method('isShippingFeeEnabled')->with(self::STORE_ID)->willReturn(true);
        $this->config->method('getShippingFeeAmount')->with(self::STORE_ID)->willReturn(5.0);
        $this->config->method('getShippingFeeCustomerGroups')->with(self::STORE_ID)->willReturn(['0']);
        $this->config->method('getShippingFeeMethods')->with(self::STORE_ID)->willReturn(['flatrate_flatrate']);

        $fee = $this->feeCalculator->getShippingFeeAmount($this->buildQuote(0), 'flatrate_flatrate');

        $this->assertNotNull($fee);
        $this->assertSame(5.0, $fee->amount);
        $this->assertSame(5.0, $fee->baseAmount);
    }

    public function testShippingFeeIsNullWhenDisabled(): void
    {
        $this->config->method('isShippingFeeEnabled')->willReturn(false);

        $this->assertNull($this->feeCalculator->getShippingFeeAmount($this->buildQuote(0), 'flatrate_flatrate'));
    }

    public function testShippingFeeIsNullWhenCustomerGroupDoesNotMatch(): void
    {
        $this->config->method('isShippingFeeEnabled')->willReturn(true);
        $this->config->method('getShippingFeeCustomerGroups')->willReturn(['3']);

        $this->assertNull($this->feeCalculator->getShippingFeeAmount($this->buildQuote(0), 'flatrate_flatrate'));
    }

    public function testShippingFeeIsNullWhenMethodDoesNotMatch(): void
    {
        $this->config->method('isShippingFeeEnabled')->willReturn(true);
        $this->config->method('getShippingFeeCustomerGroups')->willReturn(['0']);
        $this->config->method('getShippingFeeMethods')->willReturn(['tablerate_bestway']);

        $this->assertNull($this->feeCalculator->getShippingFeeAmount($this->buildQuote(0), 'flatrate_flatrate'));
    }

    public function testShippingFeeIsNullWhenMethodIsNull(): void
    {
        $this->config->method('isShippingFeeEnabled')->willReturn(true);
        $this->config->method('getShippingFeeCustomerGroups')->willReturn(['0']);

        $this->assertNull($this->feeCalculator->getShippingFeeAmount($this->buildQuote(0), null));
    }

    public function testShippingFeeIsNullWhenConfiguredAmountIsZeroOrNegative(): void
    {
        $this->config->method('isShippingFeeEnabled')->willReturn(true);
        $this->config->method('getShippingFeeCustomerGroups')->willReturn(['0']);
        $this->config->method('getShippingFeeMethods')->willReturn(['flatrate_flatrate']);
        $this->config->method('getShippingFeeAmount')->willReturn(0.0);

        $this->assertNull($this->feeCalculator->getShippingFeeAmount($this->buildQuote(0), 'flatrate_flatrate'));
    }

    public function testPaymentFeeAppliesWhenEnabledGroupAndMethodMatch(): void
    {
        $this->config->method('isPaymentFeeEnabled')->with(self::STORE_ID)->willReturn(true);
        $this->config->method('getPaymentFeeAmount')->with(self::STORE_ID)->willReturn(2.5);
        $this->config->method('getPaymentFeeCustomerGroups')->with(self::STORE_ID)->willReturn(['0']);
        $this->config->method('getPaymentFeeMethods')->with(self::STORE_ID)->willReturn(['cashondelivery']);

        $fee = $this->feeCalculator->getPaymentFeeAmount($this->buildQuote(0), 'cashondelivery');

        $this->assertNotNull($fee);
        $this->assertSame(2.5, $fee->amount);
        $this->assertSame(2.5, $fee->baseAmount);
    }

    public function testPaymentFeeIsNullWhenMethodIsNull(): void
    {
        $this->config->method('isPaymentFeeEnabled')->willReturn(true);
        $this->config->method('getPaymentFeeCustomerGroups')->willReturn(['0']);

        $this->assertNull($this->feeCalculator->getPaymentFeeAmount($this->buildQuote(0), null));
    }

    private function buildQuote(int $customerGroupId): Quote
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(self::STORE_ID);

        $quote = $this->createMock(Quote::class);
        $quote->method('getStore')->willReturn($store);
        $quote->method('getCustomerGroupId')->willReturn($customerGroupId);

        return $quote;
    }
}
