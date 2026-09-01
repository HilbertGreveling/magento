<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Plugin;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Model\FeeAmount;
use Vendor\ExtraFee\Model\FeeCalculator;
use Vendor\ExtraFee\Plugin\AppendPaymentFeeToTitle;

class AppendPaymentFeeToTitleTest extends TestCase
{
    /** @var FeeCalculator|MockObject */
    private $feeCalculator;

    /** @var PriceCurrencyInterface|MockObject */
    private $priceCurrency;

    /** @var AppendPaymentFeeToTitle */
    private AppendPaymentFeeToTitle $plugin;

    protected function setUp(): void
    {
        $this->feeCalculator = $this->createMock(FeeCalculator::class);
        $this->priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $this->priceCurrency->method('format')->willReturnCallback(
            static fn (float $amount): string => '$' . number_format($amount, 2)
        );

        $this->plugin = new AppendPaymentFeeToTitle($this->feeCalculator, $this->priceCurrency);
    }

    public function testAppendsFeeWhenApplicable(): void
    {
        $this->feeCalculator->method('getPaymentFeeAmount')->willReturn(new FeeAmount(5.0, 5.0));

        $method = $this->buildMethod('cashondelivery', $this->buildPayment());
        $result = $this->plugin->afterGetTitle($method, 'Cash On Delivery');

        $this->assertSame('Cash On Delivery (+$5.00)', $result);
    }

    public function testLeavesTitleUnchangedWhenNotApplicable(): void
    {
        $this->feeCalculator->method('getPaymentFeeAmount')->willReturn(null);

        $method = $this->buildMethod('checkmo', $this->buildPayment());
        $result = $this->plugin->afterGetTitle($method, 'Check / Money order');

        $this->assertSame('Check / Money order', $result);
    }

    public function testLeavesTitleUnchangedWhenInfoInstanceIsNotAQuotePayment(): void
    {
        $this->feeCalculator->expects($this->never())->method('getPaymentFeeAmount');

        $method = $this->createMock(AbstractMethod::class);
        $method->method('getData')->with('info_instance')->willReturn(null);

        $result = $this->plugin->afterGetTitle($method, 'Cash On Delivery');

        $this->assertSame('Cash On Delivery', $result);
    }

    public function testLeavesTitleUnchangedWhenPaymentHasNoQuote(): void
    {
        $this->feeCalculator->expects($this->never())->method('getPaymentFeeAmount');

        $payment = $this->createMock(Payment::class);
        $payment->method('getQuote')->willReturn(null);

        $result = $this->plugin->afterGetTitle($this->buildMethod('cashondelivery', $payment), 'Cash On Delivery');

        $this->assertSame('Cash On Delivery', $result);
    }

    private function buildMethod(string $code, Payment $payment): AbstractMethod
    {
        $method = $this->createMock(AbstractMethod::class);
        $method->method('getData')->with('info_instance')->willReturn($payment);
        $method->method('getCode')->willReturn($code);

        return $method;
    }

    private function buildPayment(): Payment
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getQuote')->willReturn($this->createMock(Quote::class));

        return $payment;
    }
}
