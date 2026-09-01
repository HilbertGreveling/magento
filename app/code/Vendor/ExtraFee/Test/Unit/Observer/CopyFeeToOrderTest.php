<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Observer\CopyFeeToOrder;

class CopyFeeToOrderTest extends TestCase
{
    /** @var CopyFeeToOrder */
    private CopyFeeToOrder $observer;

    protected function setUp(): void
    {
        $this->observer = new CopyFeeToOrder();
    }

    public function testCopiesFeeFromShippingAddressForNonVirtualQuote(): void
    {
        // Magento\Quote\Model\Quote\Address and Magento\Sales\Model\Order only expose these
        // fields via magic __call() setters/getters, which PHPUnit 10+ mock objects cannot
        // stub. A plain DataObject supports the same magic accessors, so it's used as a
        // lightweight stand-in for both the address and the order here.
        $address = new DataObject([
            'extra_fee_payment_amount' => '4.0000',
            'base_extra_fee_payment_amount' => '4.0000',
        ]);

        $quote = $this->createMock(Quote::class);
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->expects($this->never())->method('getBillingAddress');

        $order = new DataObject();

        $this->observer->execute($this->buildEventObserver($order, $quote));

        $this->assertSame(4.0, $order->getExtraFeePaymentAmount());
        $this->assertSame(4.0, $order->getBaseExtraFeePaymentAmount());
    }

    public function testCopiesFeeFromBillingAddressForVirtualQuote(): void
    {
        $address = new DataObject([
            'extra_fee_payment_amount' => '4.0000',
            'base_extra_fee_payment_amount' => '4.0000',
        ]);

        $quote = $this->createMock(Quote::class);
        $quote->method('isVirtual')->willReturn(true);
        $quote->method('getBillingAddress')->willReturn($address);
        $quote->expects($this->never())->method('getShippingAddress');

        $order = new DataObject();

        $this->observer->execute($this->buildEventObserver($order, $quote));

        $this->assertSame(4.0, $order->getExtraFeePaymentAmount());
        $this->assertSame(4.0, $order->getBaseExtraFeePaymentAmount());
    }

    /**
     * @param DataObject $order
     * @param Quote $quote
     * @return EventObserver
     */
    private function buildEventObserver(DataObject $order, $quote): EventObserver
    {
        $event = $this->createMock(Event::class);
        $event->method('getData')->willReturnMap([
            ['order', $order],
            ['quote', $quote],
        ]);

        $eventObserver = $this->createMock(EventObserver::class);
        $eventObserver->method('getEvent')->willReturn($event);

        return $eventObserver;
    }
}
