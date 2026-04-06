<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use SchrammelCodes\SalesRule\Exception\ConfigurationException;
use SchrammelCodes\SalesRule\Model\Config\Source\DuplicateActiveStatus;

class DuplicationConfig
{
    private const XML_PATH_CUSTOM_FIELDS = 'schrammelcodes_salesrule/duplication/custom_fields';
    private const XML_PATH_DUPLICATE_ACTIVE_STATUS = 'schrammelcodes_salesrule/duplication/duplicate_active_status';
    private const XML_PATH_COPY_WEBSITE_IDS = 'schrammelcodes_salesrule/duplication/copy_website_ids';
    private const XML_PATH_COPY_CUSTOMER_GROUP_IDS = 'schrammelcodes_salesrule/duplication/copy_customer_group_ids';
    private const XML_PATH_COPY_STORE_LABELS = 'schrammelcodes_salesrule/duplication/copy_store_labels';
    private ?array $customFieldResets = null;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * Returns a map of field names to their reset values.
     *
     * Fields are configured via Stores > Configuration > SchrammelCodes > Sales Rule.
     * An empty reset value in the configuration means the field will be set to null.
     *
     * @return array<string, mixed> Keys are DB column names, values are the reset values (null if not configured)
     */
    public function getCustomFieldResets(): array
    {
        if ($this->customFieldResets !== null) {
            return $this->customFieldResets;
        }

        $this->customFieldResets = [];
        try {
            $rows = $this->getUnserializedCustomFieldResetsConfig();
        } catch (ConfigurationException $e) {
            return $this->customFieldResets;
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (!isset($row['field_name']) || trim($row['field_name']) === '') {
                continue;
            }

            $fieldName = trim($row['field_name']);

            $resetValue = $row['reset_value'] ?? null;
            $resetValue = is_string($resetValue) && trim($resetValue) !== '' ? $resetValue : null;

            $this->customFieldResets[$fieldName] = $resetValue;
        }

        return $this->customFieldResets;
    }

    public function getDuplicateActiveStatus(): int
    {
        $activeStatus = $this->scopeConfig->getValue(self::XML_PATH_DUPLICATE_ACTIVE_STATUS);

        $activeStatus = (int)($activeStatus ?? DuplicateActiveStatus::KEEP);
        $allowedStatuses = [
            DuplicateActiveStatus::KEEP,
            DuplicateActiveStatus::DISABLED,
            DuplicateActiveStatus::ENABLED,
        ];
        if (!in_array($activeStatus, $allowedStatuses, true)) {
            return DuplicateActiveStatus::KEEP;
        }

        return $activeStatus;
    }

    public function shouldCopyWebsiteIds(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_COPY_WEBSITE_IDS);
    }

    public function shouldCopyCustomerGroupIds(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_COPY_CUSTOMER_GROUP_IDS);
    }

    public function shouldCopyStoreLabels(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_COPY_STORE_LABELS);
    }

    /**
     * @return array
     * @throws ConfigurationException
     */
    private function getUnserializedCustomFieldResetsConfig(): array
    {
        $raw = $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_FIELDS);

        if (empty($raw)) {
            throw new ConfigurationException('Empty custom fields configuration');
        }

        try {
            $rows = $this->serializer->unserialize($raw);
        } catch (\InvalidArgumentException) {
            throw new ConfigurationException('Error unserializing custom fields configuration');
        }

        if (!is_array($rows)) {
            throw new ConfigurationException('Invalid custom fields configuration');
        }

        return $rows;
    }
}
