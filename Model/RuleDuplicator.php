<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Model;

use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use SchrammelCodes\SalesRule\Api\RuleDuplicatorInterface;

class RuleDuplicator implements RuleDuplicatorInterface
{
    public function __construct(
        private readonly RuleFactory $ruleFactory,
        private readonly RuleResource $ruleResource,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function duplicate(Rule $originalRule): Rule
    {
        $newRule = $this->ruleFactory->create();
        $data = $originalRule->getData();

        $this->resetDuplicateFields($data);
        $newRule->setData($data);
        $this->copyRelationships($originalRule, $newRule);
        $this->ruleResource->save($newRule);
        $this->ruleResource->load($newRule, $newRule->getId());

        return $newRule;
    }

    /**
     * Reset fields that should not be copied to the duplicate
     *
     * Clears rule_id (for new auto-generation), dates, coupon code, usage counter,
     * and appends " (Copy)" to the rule name
     *
     * @param array &$data Rule data array (passed by reference)
     * @return void
     */
    private function resetDuplicateFields(array &$data): void
    {
        unset($data['rule_id']);
        $data['from_date'] = null;
        $data['to_date'] = null;
        $data['coupon_code'] = null;
        $data['times_used'] = 0;

        if (isset($data['name'])) {
            $data['name'] = $data['name'] . ' (Copy)';
        }
    }

    /**
     * Copy relationship data from original rule to new rule
     *
     * Copies:
     * - Website IDs (which websites the rule applies to)
     * - Customer group IDs (which customer groups can use the rule)
     * - Store labels (store-specific rule names)
     *
     * @param Rule $original The original rule to copy from
     * @param Rule $new The new rule to copy to
     * @return void
     */
    private function copyRelationships(Rule $original, Rule $new): void
    {
        if ($websiteIds = $original->getWebsiteIds()) {
            $new->setWebsiteIds($websiteIds);
        }

        if ($customerGroupIds = $original->getCustomerGroupIds()) {
            $new->setCustomerGroupIds($customerGroupIds);
        }

        if ($storeLabels = $original->getStoreLabels()) {
            $new->setStoreLabels($storeLabels);
        }
    }
}
