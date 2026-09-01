<?php
declare(strict_types=1);

namespace Vendor\ExtraFee\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;
use Vendor\ExtraFee\Helper\Config;

class ConfigTest extends TestCase
{
    public function testGetShippingFeeCustomerGroupsParsesCommaSeparatedValue(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')
            ->with('extra_fee/shipping_fee/customer_groups')
            ->willReturn('1,2,3');

        $config = $this->buildConfig($scopeConfig);

        $this->assertSame(['1', '2', '3'], $config->getShippingFeeCustomerGroups());
    }

    public function testGetShippingFeeCustomerGroupsReturnsEmptyArrayWhenNotSet(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('');

        $config = $this->buildConfig($scopeConfig);

        $this->assertSame([], $config->getShippingFeeCustomerGroups());
    }

    public function testIsShippingFeeEnabledReadsFlag(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')
            ->with('extra_fee/shipping_fee/enabled')
            ->willReturn(true);

        $config = $this->buildConfig($scopeConfig);

        $this->assertTrue($config->isShippingFeeEnabled());
    }

    private function buildConfig(ScopeConfigInterface $scopeConfig): Config
    {
        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Config($context);
    }
}
