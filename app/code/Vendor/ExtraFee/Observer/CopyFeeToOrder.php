<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

/**
 * Copies the payment fee from the quote's shipping address onto the new order.
 *
 * fieldset.xml's "sales_convert_quote_address" mechanism cannot do this: order conversion
 * populates the order via Magento\Framework\Api\DataObjectHelper::populateWithArray(), which
 * filters incoming data down to keys with a REAL (non-magic) setter method on the target class
 * (get_class_methods()). Order has no real setExtraFeePaymentAmount() method - only the generic
 * magic __call() one - so the fieldset-supplied value is silently dropped before it ever reaches
 * the order. An explicit copy is the only reliable way to get a custom field onto the order.
 */
class CopyFeeToOrder implements ObserverInterface
{
    /**
     * Copy the payment fee from the quote address onto the newly created order.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getData('order');
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        // Mirrors QuoteManagement::submit(): virtual quotes carry totals on the billing
        // address (there is no shipping address), everything else on the shipping address.
        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

        $order->setExtraFeePaymentAmount((float) $address->getExtraFeePaymentAmount());
        $order->setBaseExtraFeePaymentAmount((float) $address->getBaseExtraFeePaymentAmount());
    }
}
