<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SchrammelCodes\SalesRule\Model\RuleDuplicator;

class RuleDuplicatorTest extends TestCase
{
    private RuleDuplicator $ruleDuplicator;
    private RuleFactory|MockObject $ruleFactory;
    private RuleResource|MockObject $ruleResource;

    protected function setUp(): void
    {
        $this->ruleFactory = $this->createMock(RuleFactory::class);
        $this->ruleResource = $this->createMock(RuleResource::class);

        $this->ruleDuplicator = new RuleDuplicator(
            $this->ruleFactory,
            $this->ruleResource
        );
    }

    public function testDuplicateCreatesNewRuleWithDifferentId(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($newRule);

        $this->ruleResource->expects($this->once())
            ->method('save')
            ->with($newRule)
            ->willReturnSelf();

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->with($newRule, 999)
            ->willReturnSelf();

        $result = $this->ruleDuplicator->duplicate($originalRule);

        $this->assertSame($newRule, $result);
    }

    public function testDuplicateResetsFromDate(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $this->ruleDuplicator->duplicate($originalRule);

        $this->assertNull($capturedData['from_date']);
    }

    public function testDuplicateResetsToDate(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $this->ruleDuplicator->duplicate($originalRule);

        $this->assertNull($capturedData['to_date']);
    }

    public function testDuplicateResetsTimesUsed(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $this->ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(0, $capturedData['times_used']);
    }

    public function testDuplicateResetsCouponCode(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $this->ruleDuplicator->duplicate($originalRule);

        $this->assertNull($capturedData['coupon_code']);
    }

    public function testDuplicateAppendsNameSuffix(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $this->ruleDuplicator->duplicate($originalRule);

        $this->assertEquals('Test Rule (Copy)', $capturedData['name']);
    }

    public function testDuplicateUnsetsRuleId(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $this->ruleDuplicator->duplicate($originalRule);

        $this->assertArrayNotHasKey('rule_id', $capturedData);
    }

    public function testDuplicateCopiesWebsiteIds(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->once())
            ->method('setWebsiteIds')
            ->with([1, 2, 3]);

        $this->ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateCopiesCustomerGroupIds(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->once())
            ->method('setCustomerGroupIds')
            ->with([0, 1, 2]);

        $this->ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateCopiesStoreLabels(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->once())
            ->method('setStoreLabels')
            ->with([1 => 'Label 1', 2 => 'Label 2']);

        $this->ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateThrowsExceptionOnSaveFailure(): void
    {
        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->expects($this->once())
            ->method('save')
            ->willThrowException(new LocalizedException(__('Save failed')));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Save failed');

        $this->ruleDuplicator->duplicate($originalRule);
    }

    private function createOriginalRule(): Rule|MockObject
    {
        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getWebsiteIds', 'getCustomerGroupIds', 'getStoreLabels'])
            ->getMock();

        $rule->method('getData')->willReturn([
            'rule_id' => 123,
            'name' => 'Test Rule',
            'description' => 'Test Description',
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'coupon_code' => 'TEST123',
            'times_used' => 50,
            'is_active' => 1,
            'conditions_serialized' => 'serialized_conditions',
            'actions_serialized' => 'serialized_actions',
            'discount_amount' => 10.00,
            'simple_action' => 'by_percent',
        ]);

        $rule->method('getWebsiteIds')->willReturn([1, 2, 3]);
        $rule->method('getCustomerGroupIds')->willReturn([0, 1, 2]);
        $rule->method('getStoreLabels')->willReturn([1 => 'Label 1', 2 => 'Label 2']);

        return $rule;
    }

    private function createNewRule(): Rule|MockObject
    {
        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'setData'])
            ->addMethods(['setWebsiteIds', 'setCustomerGroupIds', 'setStoreLabels'])
            ->getMock();

        $rule->method('getId')->willReturn(999);
        $rule->method('setData')->willReturnSelf();
        $rule->method('setWebsiteIds')->willReturnSelf();
        $rule->method('setCustomerGroupIds')->willReturnSelf();
        $rule->method('setStoreLabels')->willReturnSelf();

        return $rule;
    }
}
