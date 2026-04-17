# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Module Overview

`SchrammelCodes_SalesRule` is a Magento 2 Open Source module (`magento2-module` type) that extends the built-in Cart Price Rules admin UI with:
- A "Duplicate" button on the rule edit page
- Mass actions on the rules grid: duplicate, delete, change status
- Extra grid columns: rule type, discount amount, stop rule processing
- Configurable duplication behaviour (active status, relationship copying, custom field resets) via admin system configuration
- A read-only "Duplicated From" field on the rule edit form for traceability

Requires PHP 8.1–8.3 and depends on `magento/module-sales-rule` and `magento/framework`.

## Testing

There is no `phpunit.xml` in this repo. Tests are run from within a Magento installation where the module is installed under `app/code/SchrammelCodes/SalesRule/`:

```bash
# Run all module unit tests
vendor/bin/phpunit app/code/SchrammelCodes/SalesRule/Test/Unit/

# Run a single test file
vendor/bin/phpunit app/code/SchrammelCodes/SalesRule/Test/Unit/Model/RuleDuplicatorTest.php
```

## Architecture

### Request Flow

Admin actions go through standard Magento adminhtml controllers:

```
Controller/Adminhtml/Promo/Quote/
  Duplicate.php       → GET  schrammelcodes_salesrule/promo_quote/duplicate?id=X
  MassDuplicate.php   → POST schrammelcodes_salesrule/promo_quote/massDuplicate
  MassDelete.php      → POST schrammelcodes_salesrule/promo_quote/massDelete
  MassStatus.php      → POST schrammelcodes_salesrule/promo_quote/massStatus
```

All duplication is delegated to the `RuleDuplicatorInterface` service (`Model/RuleDuplicator.php`). The interface→implementation binding is in `etc/di.xml`.

### Duplication Logic (`Model/RuleDuplicator.php`)

`RuleDuplicator::duplicate(Rule $original): Rule` copies all rule data, then resets: `rule_id`, `from_date`, `to_date`, `coupon_code`, `times_used`. Appends `" (Copy)"` to the name. Sets `duplicated_from` to the original rule's ID.

Behaviour is driven by `Model/Config/DuplicationConfig.php`:
- **Active status** of the copy: Keep, Disabled, or Enabled (configurable)
- **Website IDs, customer group IDs, store labels**: each copied only if the corresponding config flag is enabled
- **Custom fields**: additional fields to reset are configurable via admin UI

On Adobe Commerce, when `Magento_SalesRuleStaging` is present, staging fields (`created_in`, `updated_in`, `deactivated_in`) are automatically reset. No hard dependency is declared — the module detects the staging module at runtime.

### UI Extension Points

- **Button:** `Block/Adminhtml/Promo/Quote/Edit/DuplicateButton.php` implements `ButtonProviderInterface` and is wired into the form via `view/adminhtml/ui_component/sales_rule_form.xml`
- **Grid:** Mass actions and extra columns are added via `view/adminhtml/layout/sales_rule_promo_quote_index.xml`

### Configuration System

Admin panel at **Stores > Configuration > SchrammelCodes > Sales Rule > Duplication Settings**.

```
Model/Config/
  DuplicationConfig.php              → reads system config values
  Source/DuplicateActiveStatus.php   → options source for active-status dropdown

etc/adminhtml/system.xml             → admin panel definition
etc/config.xml                       → default values for all config paths
Block/Adminhtml/Config/Form/Field/CustomFields.php → dynamic table for custom field resets
Exception/ConfigurationException.php → thrown on invalid configuration
```

### Traceability

Duplicated rules track their origin via a `duplicated_from` column on the `salesrule` table.

```
etc/db_schema.xml                    → nullable int `duplicated_from` column
etc/db_schema_whitelist.json         → schema whitelist entry
Plugin/Rule/DataProviderPlugin.php   → afterGetData() enriches form data with duplicated_from
etc/adminhtml/di.xml                 → wires the plugin
view/adminhtml/ui_component/sales_rule_form.xml → read-only "Duplicated From" field
view/adminhtml/web/js/form/element/duplicated-from.js → client-side display logic
                                        (on Adobe Commerce shows both rule_id and row_id)
```

### ACL

| Permission | Gates |
|---|---|
| `SchrammelCodes_SalesRule::quote_duplicate` | Single and mass duplicate actions |
| `SchrammelCodes_SalesRule::config` | Duplication Settings configuration section |

Mass delete and status use standard `Magento_SalesRule` permissions.
