<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Model;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use SchrammelCodes\SalesRule\Api\RuleDuplicatorInterface;
use SchrammelCodes\SalesRule\Model\Config\DuplicationConfig;
use SchrammelCodes\SalesRule\Model\Config\Source\DuplicateActiveStatus;

class RuleDuplicator implements RuleDuplicatorInterface
{
    private const STAGING_MODULE = 'Magento_SalesRuleStaging';
    private const STAGING_VERSION_MAIN = 1;
    private const STAGING_VERSION_MAX = 2147483647;

    public function __construct(
        private readonly RuleFactory $ruleFactory,
        private readonly RuleResource $ruleResource,
        private readonly DuplicationConfig $duplicationConfig,
        private readonly ModuleManager $moduleManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function duplicate(Rule $originalRule): Rule
    {
        $newRule = $this->ruleFactory->create();
        $data = $originalRule->getData();

        $duplicatedFrom = $originalRule->getData($this->ruleResource->getLinkField());
        $this->resetDuplicateFields($data);
        $data['duplicated_from'] = $duplicatedFrom;
        $newRule->setData($data);
        $this->copyRelationships($originalRule, $newRule);
        $this->ruleResource->save($newRule);
        $this->ruleResource->load($newRule, $newRule->getId());

        if ($this->moduleManager->isEnabled(self::STAGING_MODULE) && ($storeLabels = $newRule->getStoreLabels())) {
            $this->ruleResource->saveStoreLabels(
                $newRule->getData($this->ruleResource->getLinkField()),
                $storeLabels
            );
        }

        return $newRule;
    }

    /**
     * Reset fields that should not be copied to the duplicate.
     *
     * Clears rule_id (for new auto-generation), dates, coupon code, usage counter,
     * appends " (Copy)" to the rule name, and applies any custom field resets
     * configured via Stores > Configuration > SchrammelCodes > Sales Rule.
     *
     * @param array &$data Rule data array (passed by reference)
     * @return void
     */
    private function resetDuplicateFields(array &$data): void
    {
        $originalActiveStatus = $data['is_active'] ?? DuplicateActiveStatus::DISABLED;

        if (isset($data['name'])) {
            $data['name'] = $data['name'] . ' (Copy)';
        }

        foreach ($this->duplicationConfig->getCustomFieldResets() as $fieldName => $resetValue) {
            $data[$fieldName] = $resetValue;
        }

        // The following fields need full reset on duplication and must not be overwritten by configuration
        unset($data['rule_id']);
        $data['from_date'] = null;
        $data['to_date'] = null;
        $data['coupon_code'] = null;
        $data['times_used'] = 0;

        $activeStatus = $this->duplicationConfig->getDuplicateActiveStatus();
        $data['is_active'] = $activeStatus === DuplicateActiveStatus::KEEP ? $originalActiveStatus : $activeStatus;

        if ($this->moduleManager->isEnabled(self::STAGING_MODULE)) {
            $data['created_in'] = self::STAGING_VERSION_MAIN;
            $data['updated_in'] = self::STAGING_VERSION_MAX;
            $data['deactivated_in'] = null;
            unset($data['row_id']);
        }
    }

    /**
     * Copy relationship data from original rule to new rule.
     *
     * Each relationship can be individually disabled via
     * Stores > Configuration > SchrammelCodes > Sales Rule > Duplication Settings.
     *
     * @param Rule $original The original rule to copy from
     * @param Rule $new The new rule to copy to
     * @return void
     */
    private function copyRelationships(Rule $original, Rule $new): void
    {
        if ($this->duplicationConfig->shouldCopyWebsiteIds() && ($websiteIds = $original->getWebsiteIds())) {
            $new->setWebsiteIds($websiteIds);
        }

        if ($this->duplicationConfig->shouldCopyCustomerGroupIds() && ($customerGroupIds = $original->getCustomerGroupIds())) {
            $new->setCustomerGroupIds($customerGroupIds);
        }

        if ($this->duplicationConfig->shouldCopyStoreLabels() && ($storeLabels = $original->getStoreLabels())) {
            $new->setStoreLabels($storeLabels);
        }
    }
}
