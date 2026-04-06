<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SchrammelCodes\SalesRule\Model\Config\DuplicationConfig;
use SchrammelCodes\SalesRule\Model\Config\Source\DuplicateActiveStatus;

class DuplicationConfigTest extends TestCase
{
    private DuplicationConfig $config;
    private ScopeConfigInterface|MockObject $scopeConfig;
    private SerializerInterface|MockObject $serializer;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->config = new DuplicationConfig(
            $this->scopeConfig,
            $this->serializer,
        );
    }

    public function testGetCustomFieldResetsReturnsEmptyArrayWhenConfigIsNull(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertEquals([], $this->config->getCustomFieldResets());
    }

    public function testGetCustomFieldResetsReturnsEmptyArrayWhenConfigIsEmpty(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('');

        $this->assertEquals([], $this->config->getCustomFieldResets());
    }

    public function testGetCustomFieldResetsReturnsEmptyArrayOnDeserializationFailure(): void
    {
        $this->scopeConfig->method('getValue')->willReturn('invalid-json');
        $this->serializer->method('unserialize')
            ->willThrowException(new \InvalidArgumentException('Invalid JSON'));

        $this->assertEquals([], $this->config->getCustomFieldResets());
    }

    public function testGetCustomFieldResetsSkipsRowsWithEmptyFieldName(): void
    {
        $rows = [
            ['field_name' => '', 'reset_value' => 'some_value'],
            ['field_name' => '   ', 'reset_value' => ''],
        ];

        $this->scopeConfig->method('getValue')->willReturn('serialized');
        $this->serializer->method('unserialize')->willReturn($rows);

        $this->assertEquals([], $this->config->getCustomFieldResets());
    }

    public function testGetCustomFieldResetsReturnsNullForEmptyResetValue(): void
    {
        $rows = [
            ['field_name' => 'reset_field', 'reset_value' => ''],
        ];

        $this->scopeConfig->method('getValue')->willReturn('serialized');
        $this->serializer->method('unserialize')->willReturn($rows);

        $result = $this->config->getCustomFieldResets();

        $this->assertArrayHasKey('reset_field', $result);
        $this->assertNull($result['reset_field']);
    }

    /**
     * @param array $rows
     * @param array $expectedValues
     * @return void
     * @dataProvider nonEmptyResetValueProvider
     */
    public function testGetCustomFieldResetsReturnsStringForNonEmptyResetValue(array $rows, array $expectedValues): void
    {
        $this->scopeConfig->method('getValue')->willReturn('serialized');
        $this->serializer->method('unserialize')->willReturn($rows);

        $result = $this->config->getCustomFieldResets();

        foreach ($expectedValues as $expectedKey => $expectedValue) {
            $this->assertArrayHasKey($expectedKey, $result);
            $this->assertEquals($expectedValue, $result[$expectedKey]);
        }
    }

    public function nonEmptyResetValueProvider(): array
    {
        return [
            'Some value' => [
                [['field_name' => 'reset_field', 'reset_value' => 'some_value']],
                ['reset_field' => 'some_value'],
            ],
            'Value 0'    => [
                [['field_name' => 'reset_field', 'reset_value' => '0']],
                ['reset_field' => '0'],
            ],
        ];
    }

    public function testGetCustomFieldResetsHandlesMultipleRows(): void
    {
        $rows = [
            ['field_name' => 'custom_field_a', 'reset_value' => ''],
            ['field_name' => 'custom_field_b', 'reset_value' => '  '],
            ['field_name' => 'custom_field_c', 'reset_value' => 'n/a'],
        ];

        $this->scopeConfig->method('getValue')->willReturn('serialized');
        $this->serializer->method('unserialize')->willReturn($rows);

        $result = $this->config->getCustomFieldResets();

        $this->assertCount(3, $result);
        $this->assertNull($result['custom_field_a']);
        $this->assertNull($result['custom_field_b']);
        $this->assertEquals('n/a', $result['custom_field_c']);
    }

    public function testGetDuplicateActiveStatusReturnsKeepWhenConfigIsNull(): void
    {
        $this->scopeConfig->method('getValue')->willReturn(null);

        $this->assertEquals(DuplicateActiveStatus::KEEP, $this->config->getDuplicateActiveStatus());
    }

    /**
     * @dataProvider duplicateActiveStatusProvider
     */
    public function testGetDuplicateActiveStatusReturnsConfiguredValue(int $configValue): void
    {
        $this->scopeConfig->method('getValue')->willReturn($configValue);

        $this->assertEquals($configValue, $this->config->getDuplicateActiveStatus());
    }

    public function duplicateActiveStatusProvider(): array
    {
        return [
            'Keep'     => [DuplicateActiveStatus::KEEP],
            'Disabled' => [DuplicateActiveStatus::DISABLED],
            'Enabled'  => [DuplicateActiveStatus::ENABLED],
        ];
    }

    public function testShouldCopyWebsiteIdsReturnsTrueWhenEnabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);

        $this->assertTrue($this->config->shouldCopyWebsiteIds());
    }

    public function testShouldCopyWebsiteIdsReturnsFalseWhenDisabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(false);

        $this->assertFalse($this->config->shouldCopyWebsiteIds());
    }

    public function testShouldCopyCustomerGroupIdsReturnsTrueWhenEnabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);

        $this->assertTrue($this->config->shouldCopyCustomerGroupIds());
    }

    public function testShouldCopyCustomerGroupIdsReturnsFalseWhenDisabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(false);

        $this->assertFalse($this->config->shouldCopyCustomerGroupIds());
    }

    public function testShouldCopyStoreLabelsReturnsTrueWhenEnabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(true);

        $this->assertTrue($this->config->shouldCopyStoreLabels());
    }

    public function testShouldCopyStoreLabelsReturnsFalseWhenDisabled(): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn(false);

        $this->assertFalse($this->config->shouldCopyStoreLabels());
    }
}
