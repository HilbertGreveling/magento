<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Model\Config\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

/**
 * All customer groups, including "NOT LOGGED IN" (guest), unlike the core
 * Magento\Customer\Model\Config\Source\Group\Multiselect which excludes it.
 */
class CustomerGroup implements ArrayInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return $this->collectionFactory->create()->toOptionArray();
    }
}
