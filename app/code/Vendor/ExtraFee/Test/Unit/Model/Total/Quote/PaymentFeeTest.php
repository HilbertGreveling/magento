<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Model\Total\Quote;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Model\FeeAmount;
use Vendor\ExtraFee\Model\FeeCalculator;
use Vendor\ExtraFee\Model\Total\Quote\PaymentFee;

class PaymentFeeTest extends TestCase
{
    /** @var FeeCalculator|MockObject */
    private $feeCalculator;

    /** @var PaymentFee */
    private PaymentFee $collector;

    protected function setUp(): void
    {
        $this->feeCalculator = $this->createMock(FeeCalculator::class);
        $this->collector = new PaymentFee($this->feeCalculator);
    }

    public function testFeeIsAddedWhenApplicable(): void
    {
        $this->feeCalculator->method('getPaymentFeeAmount')
            ->with($this->isInstanceOf(Quote::class), 'checkmo')
            ->willReturn(new FeeAmount(2.5, 2.5));

        $quote = $this->buildQuote('checkmo', [$this->buildAddress()]);
        $total = new Total([], new Json());
        $this->collector->collect($quote, $this->buildShippingAssignment($quote), $total);

        $this->assertSame(2.5, $total->getTotalAmount('extra_fee_payment'));
        $fetched = $this->collector->fetch($quote, new Total([], new Json()));
        $this->assertSame(2.5, $fetched['value']);
    }

    public function testFeeIsNotAddedWhenNotApplicable(): void
    {
        $this->feeCalculator->method('getPaymentFeeAmount')->willReturn(null);

        $quote = $this->buildQuote('banktransfer', [$this->buildAddress()]);
        $total = new Total([], new Json());
        $this->collector->collect($quote, $this->buildShippingAssignment($quote), $total);

        $this->assertSame(0.0, (float) $total->getTotalAmount('extra_fee_payment'));
        $this->assertNull($this->collector->fetch($quote, new Total([], new Json())));
    }

    /**
     * On a multi-shipping quote (multiple shipping addresses), the fee is applied
     * independently to each address, not just the first one.
     */
    public function testFeeIsAppliedToEachAddressForMultiShippingQuote(): void
    {
        $this->feeCalculator->method('getPaymentFeeAmount')->willReturn(new FeeAmount(2.5, 2.5));

        $firstAddress = $this->buildAddress();
        $secondAddress = $this->buildAddress();
        $quote = $this->buildQuote('checkmo', [$firstAddress, $secondAddress]);

        $totalForFirst = new Total([], new Json());
        $this->collector->collect($quote, $this->wrapAddress($firstAddress), $totalForFirst);
        $this->assertSame(2.5, $totalForFirst->getTotalAmount('extra_fee_payment'));

        $totalForSecond = new Total([], new Json());
        $this->collector->collect($quote, $this->wrapAddress($secondAddress), $totalForSecond);
        $this->assertSame(2.5, $totalForSecond->getTotalAmount('extra_fee_payment'));
    }

    /**
     * @param Address[] $shippingAddresses
     */
    private function buildQuote(string $paymentMethod, array $shippingAddresses): Quote
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getMethod')->willReturn($paymentMethod);

        $quote = $this->createMock(Quote::class);
        $quote->method('getPayment')->willReturn($payment);
        $quote->method('getAllShippingAddresses')->willReturn($shippingAddresses);

        return $quote;
    }

    private function buildAddress(): Address
    {
        return $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function buildShippingAssignment(Quote $quote): ShippingAssignmentInterface
    {
        $addresses = $quote->getAllShippingAddresses();

        return $this->wrapAddress($addresses[0]);
    }

    private function wrapAddress(Address $address): ShippingAssignmentInterface
    {
        $shipping = $this->createMock(ShippingInterface::class);
        $shipping->method('getAddress')->willReturn($address);

        $shippingAssignment = $this->createMock(ShippingAssignmentInterface::class);
        $shippingAssignment->method('getShipping')->willReturn($shipping);

        return $shippingAssignment;
    }
}
