<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SchrammelCodes\SalesRule\Model\Config\DuplicationConfig;
use SchrammelCodes\SalesRule\Model\Config\Source\DuplicateActiveStatus;
use SchrammelCodes\SalesRule\Model\RuleDuplicator;

class RuleDuplicatorTest extends TestCase
{
    private RuleFactory|MockObject $ruleFactory;
    private RuleResource|MockObject $ruleResource;

    public function testDuplicateCreatesNewRuleWithDifferentId(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $result = $ruleDuplicator->duplicate($originalRule);

        $this->assertSame($newRule, $result);
    }

    public function testDuplicateResetsFromDate(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertNull($capturedData['from_date']);
    }

    public function testDuplicateResetsToDate(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertNull($capturedData['to_date']);
    }

    public function testDuplicateResetsTimesUsed(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(0, $capturedData['times_used']);
    }

    public function testDuplicateResetsCouponCode(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertNull($capturedData['coupon_code']);
    }

    public function testDuplicateAppendsNameSuffix(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals('Test Rule (Copy)', $capturedData['name']);
    }

    public function testDuplicateUnsetsRuleId(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertArrayNotHasKey('rule_id', $capturedData);
    }

    public function testDuplicateCopiesWebsiteIds(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->once())
            ->method('setWebsiteIds')
            ->with([1, 2, 3]);

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateSkipsWebsiteIdsWhenDisabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator([], false);

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->never())->method('setWebsiteIds');

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateCopiesCustomerGroupIds(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->once())
            ->method('setCustomerGroupIds')
            ->with([0, 1, 2]);

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateSkipsCustomerGroupIdsWhenDisabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator([], true, false);

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->never())->method('setCustomerGroupIds');

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateCopiesStoreLabels(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->once())
            ->method('setStoreLabels')
            ->with([1 => 'Label 1', 2 => 'Label 2']);

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateSkipsStoreLabelsWhenDisabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator([], true, true, false);

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $newRule->expects($this->never())->method('setStoreLabels');

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateKeepsIsActiveWhenStatusIsKeep(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(1, $capturedData['is_active']);
    }

    public function testDuplicateKeepsIsActiveWhenStatusIsKeepAndOverridePresentInCustomFields(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(['is_active' => 0]);

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(1, $capturedData['is_active']);
    }

    public function testDuplicateSetsIsActiveToZeroWhenStatusIsDisabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(
            [],
            true,
            true,
            true,
            DuplicateActiveStatus::DISABLED
        );

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(0, $capturedData['is_active']);
    }

    public function testDuplicateSetsIsActiveToOneWhenStatusIsEnabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(
            [],
            true,
            true,
            true,
            DuplicateActiveStatus::ENABLED
        );

        $originalRule = $this->createOriginalRule(['is_active' => 0]);
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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(1, $capturedData['is_active']);
    }

    public function testDuplicateThrowsExceptionOnSaveFailure(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->expects($this->once())
            ->method('save')
            ->willThrowException(new LocalizedException(__('Save failed')));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Save failed');

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateResetsCustomFieldToNull(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(['reset_field' => null]);

        $originalRule = $this->createOriginalRule(['reset_field' => 'original_value']);
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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertArrayHasKey('reset_field', $capturedData);
        $this->assertNull($capturedData['reset_field']);
    }

    public function testDuplicateResetsCustomFieldToConfiguredValue(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(['reset_field' => 'some_value']);

        $originalRule = $this->createOriginalRule(['reset_field' => 'original_value']);
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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals('some_value', $capturedData['reset_field']);
    }

    public function testDuplicateResetsMultipleCustomFields(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator([
            'custom_field_a' => null,
            'custom_field_b' => 'n/a',
        ]);

        $originalRule = $this->createOriginalRule(['custom_field_a' => 'value_a', 'custom_field_b' => 'value_b']);
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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertNull($capturedData['custom_field_a']);
        $this->assertEquals('n/a', $capturedData['custom_field_b']);
    }

    public function testDuplicateSetsCustomFieldEvenIfNotInOriginalData(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(['reset_field' => null]);

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

        $ruleDuplicator->duplicate($originalRule);

        $this->assertArrayHasKey('reset_field', $capturedData);
        $this->assertNull($capturedData['reset_field']);
    }

    private function getRuleDuplicator(
        array $customFieldResets = [],
        bool $shouldCopyWebsiteIds = true,
        bool $shouldCopyCustomerGroupIds = true,
        bool $shouldCopyStoreLabels = true,
        int $duplicateActiveStatus = DuplicateActiveStatus::KEEP
    ): RuleDuplicator
    {
        $this->ruleFactory = $this->createMock(RuleFactory::class);
        $this->ruleResource = $this->createMock(RuleResource::class);
        $duplicationConfig = $this->createMock(DuplicationConfig::class);
        $duplicationConfig->method('getCustomFieldResets')->willReturn($customFieldResets);
        $duplicationConfig->method('shouldCopyWebsiteIds')->willReturn($shouldCopyWebsiteIds);
        $duplicationConfig->method('shouldCopyCustomerGroupIds')->willReturn($shouldCopyCustomerGroupIds);
        $duplicationConfig->method('shouldCopyStoreLabels')->willReturn($shouldCopyStoreLabels);
        $duplicationConfig->method('getDuplicateActiveStatus')->willReturn($duplicateActiveStatus);

        return new RuleDuplicator(
            $this->ruleFactory,
            $this->ruleResource,
            $duplicationConfig
        );
    }

    private function createOriginalRule(array $extraData = []): Rule|MockObject
    {
        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getWebsiteIds', 'getCustomerGroupIds', 'getStoreLabels'])
            ->getMock();

        $rule->method('getData')->willReturn(array_merge([
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
        ], $extraData));

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
