<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Plugin;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Api\Data\OrderExtension;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Plugin\AddExtraFeeToOrderExtensionAttributes;

class AddExtraFeeToOrderExtensionAttributesTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private $repository;

    /** @var AddExtraFeeToOrderExtensionAttributes */
    private AddExtraFeeToOrderExtensionAttributes $plugin;

    /** @var ObjectManager */
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(OrderRepositoryInterface::class);
        $this->plugin = new AddExtraFeeToOrderExtensionAttributes();
        $this->objectManager = new ObjectManager($this);
    }

    public function testAfterGetPopulatesExtensionAttributesFromOrderColumns(): void
    {
        $order = $this->buildOrder('4.0000', '4.0000');

        $result = $this->plugin->afterGet($this->repository, $order);

        $this->assertSame($order, $result);
        $this->assertSame(4.0, $order->getExtensionAttributes()->getExtraFeePaymentAmount());
        $this->assertSame(4.0, $order->getExtensionAttributes()->getBaseExtraFeePaymentAmount());
    }

    public function testAfterGetReusesExistingExtensionAttributesInstance(): void
    {
        $extension = new OrderExtension();
        $order = $this->buildOrder('2.5000', '2.5000');
        $order->setExtensionAttributes($extension);

        $this->plugin->afterGet($this->repository, $order);

        $this->assertSame($extension, $order->getExtensionAttributes());
        $this->assertSame(2.5, $extension->getExtraFeePaymentAmount());
        $this->assertSame(2.5, $extension->getBaseExtraFeePaymentAmount());
    }

    public function testAfterGetListPopulatesExtensionAttributesOnEveryItem(): void
    {
        $firstOrder = $this->buildOrder('4.0000', '4.0000');
        $secondOrder = $this->buildOrder('0.0000', '0.0000');

        $searchResult = $this->createMock(OrderSearchResultInterface::class);
        $searchResult->method('getItems')->willReturn([$firstOrder, $secondOrder]);

        $result = $this->plugin->afterGetList($this->repository, $searchResult);

        $this->assertSame($searchResult, $result);
        $this->assertSame(4.0, $firstOrder->getExtensionAttributes()->getExtraFeePaymentAmount());
        $this->assertSame(0.0, $secondOrder->getExtensionAttributes()->getExtraFeePaymentAmount());
    }

    /**
     * Magento\Sales\Api\Data\OrderInterface doesn't declare getExtraFeePaymentAmount()
     * (it's a custom column only reachable via magic __call() on the concrete Order model),
     * so createMock(OrderInterface::class) can't stub it. A real Order instance, built via
     * the object manager helper, exercises the actual magic getter/setter. Order's
     * extensionAttributesFactory dependency is configured to build real OrderExtension
     * instances, matching Order::getExtensionAttributes()'s real auto-populate behavior
     * (it never returns null in production).
     *
     * @param string $amount
     * @param string $baseAmount
     * @return Order
     */
    private function buildOrder(string $amount, string $baseAmount): Order
    {
        $extensionAttributesFactory = $this->createMock(ExtensionAttributesFactory::class);
        $extensionAttributesFactory->method('create')->willReturn(new OrderExtension());

        /** @var Order $order */
        $order = $this->objectManager->getObject(Order::class, [
            'extensionFactory' => $extensionAttributesFactory,
        ]);
        $order->setData('extra_fee_payment_amount', $amount);
        $order->setData('base_extra_fee_payment_amount', $baseAmount);

        return $order;
    }
}
