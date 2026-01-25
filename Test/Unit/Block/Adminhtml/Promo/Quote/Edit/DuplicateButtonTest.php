<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Block\Adminhtml\Promo\Quote\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\SalesRule\Model\RegistryConstants;
use Magento\SalesRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SchrammelCodes\SalesRule\Block\Adminhtml\Promo\Quote\Edit\DuplicateButton;

class DuplicateButtonTest extends TestCase
{
    private DuplicateButton $button;
    private Context|MockObject $context;
    private Registry|MockObject $registry;
    private UrlInterface|MockObject $urlBuilder;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
        $this->urlBuilder = $this->createMock(UrlInterface::class);

        $this->context->method('getUrlBuilder')->willReturn($this->urlBuilder);

        $this->button = new DuplicateButton($this->context, $this->registry);
    }

    public function testGetButtonDataWithRule(): void
    {
        $ruleId = 123;
        $duplicateUrl = 'https://example.com/admin/schrammelcodes_salesrule/promo_quote/duplicate/id/123';

        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn($ruleId);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with(RegistryConstants::CURRENT_SALES_RULE)
            ->willReturn($rule);

        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('schrammelcodes_salesrule/promo_quote/duplicate', ['id' => $ruleId])
            ->willReturn($duplicateUrl);

        $result = $this->button->getButtonData();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('class', $result);
        $this->assertArrayHasKey('on_click', $result);
        $this->assertArrayHasKey('sort_order', $result);
        $this->assertEquals('Duplicate', $result['label']->getText());
        $this->assertEquals('duplicate', $result['class']);
        $this->assertEquals("location.href = '{$duplicateUrl}';", $result['on_click']);
        $this->assertEquals(15, $result['sort_order']);
    }

    public function testGetButtonDataWithoutRule(): void
    {
        $this->registry->expects($this->once())
            ->method('registry')
            ->with(RegistryConstants::CURRENT_SALES_RULE)
            ->willReturn(null);

        $this->urlBuilder->expects($this->never())
            ->method('getUrl');

        $result = $this->button->getButtonData();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetButtonDataWithRuleWithoutId(): void
    {
        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn(null);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with(RegistryConstants::CURRENT_SALES_RULE)
            ->willReturn($rule);

        $this->urlBuilder->expects($this->never())
            ->method('getUrl');

        $result = $this->button->getButtonData();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
