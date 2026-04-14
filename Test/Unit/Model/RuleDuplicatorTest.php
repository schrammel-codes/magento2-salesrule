<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
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
        $ruleDuplicator = $this->getRuleDuplicator(shouldCopyWebsiteIds: false);

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
        $ruleDuplicator = $this->getRuleDuplicator(shouldCopyCustomerGroupIds: false);

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
        $ruleDuplicator = $this->getRuleDuplicator(shouldCopyStoreLabels: false);

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
        $ruleDuplicator = $this->getRuleDuplicator(duplicateActiveStatus: DuplicateActiveStatus::DISABLED);

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
        $ruleDuplicator = $this->getRuleDuplicator(duplicateActiveStatus: DuplicateActiveStatus::ENABLED);

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

    public function testDuplicateResetsStagingFieldsWhenStagingModuleIsEnabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(stagingEnabled: true);

        $originalRule = $this->createOriginalRule(['row_id' => 42]);
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

        $this->assertEquals(1, $capturedData['created_in']);
        $this->assertEquals(2147483647, $capturedData['updated_in']);
        $this->assertNull($capturedData['deactivated_in']);
        $this->assertArrayNotHasKey('row_id', $capturedData);
    }

    public function testDuplicateDoesNotResetStagingFieldsWhenStagingModuleIsDisabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

        $originalRule = $this->createOriginalRule(['row_id' => 42, 'created_in' => 5, 'updated_in' => 100]);
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

        // Values from the original rule must not be overwritten by staging logic
        $this->assertEquals(42, $capturedData['row_id']);
        $this->assertEquals(5, $capturedData['created_in']);
        $this->assertEquals(100, $capturedData['updated_in']);
    }

    public function testDuplicateSavesStoreLabelsWithLinkFieldWhenStagingModuleIsEnabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(stagingEnabled: true);

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();
        $this->ruleResource->method('getLinkField')->willReturn('row_id');

        $newRule->method('getStoreLabels')->willReturn([1 => 'Label 1', 2 => 'Label 2']);
        $newRule->method('getData')->with('row_id')->willReturn(77);

        $this->ruleResource->expects($this->once())
            ->method('saveStoreLabels')
            ->with(77, [1 => 'Label 1', 2 => 'Label 2']);

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateDoesNotSaveStoreLabelsWhenStagingModuleIsDisabled(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();

        $this->ruleResource->expects($this->never())->method('saveStoreLabels');

        $ruleDuplicator->duplicate($originalRule);
    }

    public function testDuplicateSetsLinkFieldValueAsDuplicatedFromOnOpenSource(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator();

        $originalRule = $this->createOriginalRule();
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();
        $this->ruleResource->method('getLinkField')->willReturn('rule_id');

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(123, $capturedData['duplicated_from']);
    }

    public function testDuplicateSetsRowIdAsDuplicatedFromOnCommerce(): void
    {
        $ruleDuplicator = $this->getRuleDuplicator(stagingEnabled: true);

        $originalRule = $this->createOriginalRule(['row_id' => 42]);
        $newRule = $this->createNewRule();

        $this->ruleFactory->method('create')->willReturn($newRule);
        $this->ruleResource->method('save')->willReturnSelf();
        $this->ruleResource->method('load')->willReturnSelf();
        $this->ruleResource->method('getLinkField')->willReturn('row_id');
        $newRule->method('getStoreLabels')->willReturn([]);

        $capturedData = null;
        $newRule->expects($this->once())
            ->method('setData')
            ->willReturnCallback(function ($data) use (&$capturedData, $newRule) {
                $capturedData = $data;

                return $newRule;
            });

        $ruleDuplicator->duplicate($originalRule);

        $this->assertEquals(42, $capturedData['duplicated_from']);
    }

    private function getRuleDuplicator(
        array $customFieldResets = [],
        bool $shouldCopyWebsiteIds = true,
        bool $shouldCopyCustomerGroupIds = true,
        bool $shouldCopyStoreLabels = true,
        int $duplicateActiveStatus = DuplicateActiveStatus::KEEP,
        bool $stagingEnabled = false
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

        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')
            ->with('Magento_SalesRuleStaging')
            ->willReturn($stagingEnabled);

        return new RuleDuplicator(
            $this->ruleFactory,
            $this->ruleResource,
            $duplicationConfig,
            $moduleManager
        );
    }

    private function createOriginalRule(array $extraData = []): Rule|MockObject
    {
        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getWebsiteIds', 'getCustomerGroupIds', 'getStoreLabels'])
            ->getMock();

        $data = array_merge([
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
        ], $extraData);

        $rule->method('getData')->willReturnCallback(
            fn(?string $key = null) => $key === null ? $data : ($data[$key] ?? null)
        );

        $rule->method('getWebsiteIds')->willReturn([1, 2, 3]);
        $rule->method('getCustomerGroupIds')->willReturn([0, 1, 2]);
        $rule->method('getStoreLabels')->willReturn([1 => 'Label 1', 2 => 'Label 2']);

        return $rule;
    }

    private function createNewRule(): Rule|MockObject
    {
        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'setData', 'getData', 'getStoreLabels'])
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
