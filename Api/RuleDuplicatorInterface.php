<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Model\Rule;

interface RuleDuplicatorInterface
{
    /**
     * Duplicate a cart price rule
     *
     * Creates a new rule with:
     * - New rule_id (auto-generated)
     * - Reset from_date, to_date, times_used
     * - Name with " (Copy)" suffix
     * - All conditions and actions copied
     * - Customer group associations copied
     * - Website assignments copied
     * - Store labels copied
     *
     * @param Rule $originalRule The rule to duplicate
     * @return Rule The newly created duplicate rule
     * @throws LocalizedException If duplication fails
     */
    public function duplicate(Rule $originalRule): Rule;
}
