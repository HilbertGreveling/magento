<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Model\Total\Quote;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Model\FeeAmount;
use Vendor\ExtraFee\Model\FeeCalculator;
use Vendor\ExtraFee\Model\Total\Quote\ShippingFee;

class ShippingFeeTest extends TestCase
{
    /** @var FeeCalculator|MockObject */
    private $feeCalculator;

    /** @var ShippingFee */
    private ShippingFee $collector;

    protected function setUp(): void
    {
        $this->feeCalculator = $this->createMock(FeeCalculator::class);
        $this->collector = new ShippingFee($this->feeCalculator);
    }

    public function testFeeIsMergedIntoShippingAmountWhenApplicable(): void
    {
        $this->feeCalculator->method('getShippingFeeAmount')
            ->with($this->isInstanceOf(Quote::class), 'flatrate_flatrate')
            ->willReturn(new FeeAmount(5.0, 5.0));

        $total = new Total([], new Json());
        $total->setTotalAmount('shipping', 4.99);
        $total->setBaseTotalAmount('shipping', 4.99);

        $this->collector->collect($this->buildQuote(), $this->buildShippingAssignment('flatrate_flatrate'), $total);

        $this->assertSame(9.99, $total->getShippingAmount());
    }

    public function testShippingAmountUnchangedWhenNotApplicable(): void
    {
        $this->feeCalculator->method('getShippingFeeAmount')->willReturn(null);

        $total = new Total([], new Json());
        $total->setTotalAmount('shipping', 4.99);
        $total->setBaseTotalAmount('shipping', 4.99);

        $this->collector->collect($this->buildQuote(), $this->buildShippingAssignment('flatrate_flatrate'), $total);

        $this->assertSame(4.99, $total->getShippingAmount());
    }

    public function testFetchReturnsNullSinceThereIsNoSeparateTotalsLine(): void
    {
        $this->assertNull($this->collector->fetch($this->buildQuote(), new Total([], new Json())));
    }

    private function buildQuote(): Quote
    {
        return $this->createMock(Quote::class);
    }

    private function buildShippingAssignment(string $shippingMethod): ShippingAssignmentInterface
    {
        $address = new class ($shippingMethod) extends Address {
            /** @var string */
            private string $shippingMethod;

            public function __construct(string $shippingMethod)
            {
                $this->shippingMethod = $shippingMethod;
            }

            public function getShippingMethod()
            {
                return $this->shippingMethod;
            }
        };

        $shipping = $this->createMock(ShippingInterface::class);
        $shipping->method('getAddress')->willReturn($address);

        $shippingAssignment = $this->createMock(ShippingAssignmentInterface::class);
        $shippingAssignment->method('getShipping')->willReturn($shipping);

        return $shippingAssignment;
    }
}
