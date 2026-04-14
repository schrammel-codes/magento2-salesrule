<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Plugin\Rule;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\SalesRule\Model\Rule\DataProvider;

class DataProviderPlugin
{
    private const STAGING_MODULE = 'Magento_SalesRuleStaging';

    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Enrich duplicated_from data for Adobe Commerce installations.
     *
     * On Commerce, duplicated_from holds the row_id of the original rule.
     * We look up the rule_id that corresponds to that row_id via a direct DB query,
     * since the EntityManager always queries by identifierField (rule_id), making
     * RuleRepository::getById() and RuleResource::load() unusable for row_id lookups.
     *
     * @param DataProvider $subject
     * @param array|null $result
     * @return array|null
     */
    public function afterGetData(DataProvider $subject, ?array $result): ?array
    {
        if ($result === null) {
            return $result;
        }
        
        if (!$this->moduleManager->isEnabled(self::STAGING_MODULE) || empty($result)) {
            foreach ($result as $ruleId => $ruleData) {
                $result[$ruleId]['stored_identifier_field'] = 'rule_id';
            }

            return $result;
        }

        $connection = $this->resourceConnection->getConnection();
        foreach ($result as $ruleId => $ruleData) {
            $duplicatedFrom = $ruleData['duplicated_from'] ?? null;
            if ($duplicatedFrom === null) {
                continue;
            }
            $result[$ruleId]['stored_identifier_field'] = 'row_id';

            $originalRuleId = $connection->fetchOne(
                $connection->select()
                    ->from($connection->getTableName('salesrule'), ['rule_id'])
                    ->where('row_id = ?', (int)$duplicatedFrom)
            );
            if ($originalRuleId) {
                $result[$ruleId]['duplicated_from_rule_id'] = (int)$originalRuleId;
            }
            // If no row found, original rule was deleted — skip enrichment silently.
        }

        return $result;
    }
}
