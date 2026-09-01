<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Exposes the already-persisted payment fee columns as order extension attributes, so they
 * appear under "extension_attributes" in the Sales REST/GraphQL API response. The columns
 * themselves are populated by Observer\CopyFeeToOrder; this plugin only mirrors that already
 * -correct value into the extension attributes container for API consumers.
 */
class AddExtraFeeToOrderExtensionAttributes
{
    /**
     * Populate extension attributes on a single order.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order): OrderInterface
    {
        $this->addExtraFeeExtensionAttribute($order);

        return $order;
    }

    /**
     * Populate extension attributes on every order in a search result.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     * @return OrderSearchResultInterface
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $searchResult
    ): OrderSearchResultInterface {
        foreach ($searchResult->getItems() as $order) {
            $this->addExtraFeeExtensionAttribute($order);
        }

        return $searchResult;
    }

    /**
     * Populate extension attributes on a single order from its already-persisted columns.
     *
     * @param OrderInterface $order
     * @return void
     */
    private function addExtraFeeExtensionAttribute(OrderInterface $order): void
    {
        // Order::getExtensionAttributes() always auto-populates a real instance, never null
        $extensionAttributes = $order->getExtensionAttributes();

        $extensionAttributes->setExtraFeePaymentAmount((float) $order->getExtraFeePaymentAmount());
        $extensionAttributes->setBaseExtraFeePaymentAmount((float) $order->getBaseExtraFeePaymentAmount());

        $order->setExtensionAttributes($extensionAttributes);
    }
}
