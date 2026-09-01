<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Cart\ShippingMethod;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Model\FeeAmount;
use Vendor\ExtraFee\Model\FeeCalculator;
use Vendor\ExtraFee\Plugin\AddFeeToShippingMethodAmount;

class AddFeeToShippingMethodAmountTest extends TestCase
{
    /** @var FeeCalculator|MockObject */
    private $feeCalculator;

    /** @var AddFeeToShippingMethodAmount */
    private AddFeeToShippingMethodAmount $plugin;

    /** @var ObjectManager */
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->feeCalculator = $this->createMock(FeeCalculator::class);
        $this->plugin = new AddFeeToShippingMethodAmount($this->feeCalculator);
        $this->objectManager = new ObjectManager($this);
    }

    public function testAddsFeeToRateWhenApplicable(): void
    {
        $this->feeCalculator->method('getShippingFeeAmount')
            ->with($this->isInstanceOf(Quote::class), 'flatrate_flatrate')
            ->willReturn(new FeeAmount(5.0, 5.0));

        $result = $this->plugin->afterModelToDataObject(
            $this->createMock(ShippingMethodConverter::class),
            $this->buildResult(4.99),
            $this->buildRateModel(),
            'USD'
        );

        $this->assertSame(9.99, $result->getAmount());
        $this->assertSame(9.99, $result->getBaseAmount());
        $this->assertSame(9.99, $result->getPriceExclTax());
        $this->assertSame(9.99, $result->getPriceInclTax());
    }

    public function testLeavesRateUnchangedWhenNotApplicable(): void
    {
        $this->feeCalculator->method('getShippingFeeAmount')->willReturn(null);

        $result = $this->plugin->afterModelToDataObject(
            $this->createMock(ShippingMethodConverter::class),
            $this->buildResult(4.99),
            $this->buildRateModel(),
            'USD'
        );

        $this->assertSame(4.99, $result->getAmount());
    }

    public function testLeavesRateUnchangedWhenNoQuote(): void
    {
        $this->feeCalculator->expects($this->never())->method('getShippingFeeAmount');

        $rateModel = $this->createMock(Rate::class);
        $rateModel->method('getAddress')->willReturn(null);

        $result = $this->plugin->afterModelToDataObject(
            $this->createMock(ShippingMethodConverter::class),
            $this->buildResult(4.99),
            $rateModel,
            'USD'
        );

        $this->assertSame(4.99, $result->getAmount());
    }

    private function buildResult(float $amount): ShippingMethodInterface
    {
        /** @var ShippingMethod $result */
        $result = $this->objectManager->getObject(ShippingMethod::class);
        $result->setCarrierCode('flatrate');
        $result->setMethodCode('flatrate');
        $result->setAmount($amount);
        $result->setBaseAmount($amount);
        $result->setPriceExclTax($amount);
        $result->setPriceInclTax($amount);

        return $result;
    }

    private function buildRateModel(): Rate
    {
        $quote = $this->createMock(Quote::class);

        $address = $this->createMock(Address::class);
        $address->method('getQuote')->willReturn($quote);

        $rateModel = $this->createMock(Rate::class);
        $rateModel->method('getAddress')->willReturn($address);

        return $rateModel;
    }
}
