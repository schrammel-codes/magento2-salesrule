<h1 align="center">SchrammelCodes_SalesRule</h1> 

<div align="center">
    <p>Streamline your promotional rule management with powerful duplication and mass management features for Magento 2 Cart Price Rules.</p>
    <img src="https://img.shields.io/badge/magento-2.4-brightgreen.svg?logo=magento&longCache=true&style=flat-square" alt="Supported Magento Versions" />
    <a href="https://packagist.org/packages/schrammel-codes/magento2-salesrule" target="_blank"><img src="https://img.shields.io/packagist/v/schrammel-codes/magento2-salesrule.svg?style=flat-square" alt="Latest Stable Version" /></a>
    <a href="https://packagist.org/packages/schrammel-codes/magento2-salesrule" target="_blank"><img src="https://poser.pugx.org/schrammel-codes/magento2-salesrule/downloads" alt="Composer Downloads" /></a>
    <a href="https://github.com/schrammel-codes/magento2-salesrule/graphs/commit-activity" target="_blank"><img src="https://img.shields.io/badge/maintained%3F-yes-brightgreen.svg?style=flat-square" alt="Maintained - Yes" /></a>
    <a href="https://opensource.org/licenses/MIT" target="_blank"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License - MIT"/></a>
</div>

## What This Module Does

This module extends Magento 2's Cart Price Rules (promotional discounts) with convenient management features that save time and reduce errors when managing multiple similar promotions.

### Key Features

**1. Duplicate Cart Price Rules**

Create copies of existing promotional rules with a single click. Perfect for:
- Creating seasonal variations of successful promotions
- Setting up similar rules for different customer segments
- Testing rule variations without risking the original configuration

**2. Mass Actions**

Manage multiple rules at once:
- **Mass Duplicate**: Copy several rules simultaneously
- **Mass Delete**: Remove multiple outdated rules in one action
- **Mass Status Change**: Enable or disable multiple rules at once

### How It Works

#### Smart Duplication

When you duplicate a rule, the module:
- ✅ Copies all rule conditions and actions
- ✅ Adds "(Copy)" suffix to the rule name _(overridable via Custom Fields configuration — see below)_
- ✅ Keeps original active/inactive status _(configurable — can force enabled or disabled)_
- ✅ Copies customer group associations _(configurable)_
- ✅ Copies website assignments _(configurable)_
- ✅ Copies store-specific labels _(configurable)_
- 🔄 Resets coupon code _(always reset, cannot be overridden by configuration)_
- 🔄 Resets usage counter to zero _(always reset, cannot be overridden by configuration)_
- 🔄 Clears start and end dates _(always reset, cannot be overridden by configuration)_
- 🔄 Generates a new rule ID automatically _(always reset, cannot be overridden by configuration)_
- 🔄 Resets custom fields configured via **Stores > Configuration > SchrammelCodes > Sales Rule** _(configurable)_

This ensures your duplicated rules are ready to customize without inheriting usage history or active date ranges.

## Benefits for Store Administrators

### Time Savings
Instead of manually recreating complex promotional rules with dozens of conditions, duplicate existing ones and adjust only what's needed. A task that might take 15-20 minutes now takes seconds.

### Consistency
Duplicating rules ensures all settings, conditions, and actions are copied exactly, reducing human error when creating similar promotions.

### Flexibility
Quickly test variations of successful promotions or create region-specific versions of global campaigns without starting from scratch.

### Efficiency
Mass actions let you manage multiple rules at once - perfect for seasonal cleanup, enabling holiday promotions, or deactivating expired campaigns.

## Usage Guide

### Duplicate a Single Rule

![action-dropdown.png](docs/action-dropdown.png)

1. Navigate to **Marketing > Promotions > Cart Price Rules**
2. Find the rule you want to duplicate
3. Click **Select** in the Actions column
4. Choose **Duplicate**
5. The duplicated rule appears in the grid with "(Copy)" appended to its name

![duplicated-rule.png](docs/duplicated-rule.png)

### Duplicate from Rule Edit Page

![duplicate-on-edit.png](docs/duplicate-on-edit.png)

1. Open any Cart Price Rule for editing
2. Click the **Duplicate Rule** button (next to Save/Delete)
3. You'll be redirected to the edit page of the newly created copy
4. Customize the copy as needed and save

### Mass Duplicate Multiple Rules

1. In the Cart Price Rules grid, select checkboxes for rules you want to duplicate
2. Open the **Actions** dropdown (top left of grid)
3. Select **Duplicate**
4. Click **Submit**
5. All selected rules are duplicated at once

### Mass Delete Rules

1. Select rules to delete using checkboxes
2. Choose **Delete** from the Actions dropdown
3. Confirm the deletion
4. Selected rules are permanently removed

### Mass Status Change

1. Select rules to enable or disable
2. Choose **Change Status** from the Actions dropdown
3. Select **Enabled** or **Disabled**
4. Click **Submit**
5. All selected rules are updated immediately

## Installation

```bash
# Enable the module
bin/magento module:enable SchrammelCodes_SalesRule

# Run setup upgrade
bin/magento setup:upgrade

# Clear cache
bin/magento cache:clean
```
### Permissions

The module adds two ACL resources:
- `SchrammelCodes_SalesRule::quote_duplicate` - Permission to duplicate cart price rules
- `SchrammelCodes_SalesRule::config` - Permission to manage the module configuration

Grant these permissions to admin roles as needed.

## Compatibility

- **Magento 2.4.x** (Open Source)
- **PHP 8.1, 8.2, 8.3**

> ### For Magento Commerce installations
> To ensure proper reset of staging preview data, install the companion module **SchrammelCodes_SalesRuleCommerce**
> to ensure proper handling of staging fields.

## Duplication Configuration

All duplication behaviour is configurable via **Stores > Configuration > SchrammelCodes > Sales Rule > Duplication Settings**.

> No `bin/magento setup:upgrade` is required — this is purely configuration-based.

### Relationship Copying

Control whether each type of association is carried over to the duplicate:

| Setting | Default | Description |
|---------|---------|-------------|
| **Duplicate Active Status** | Keep as is | Set the active/inactive status of duplicated rules: keep the original value, always disable, or always enable |
| **Copy Website Associations** | Yes | Copy which websites the rule is assigned to |
| **Copy Customer Group Associations** | Yes | Copy which customer groups the rule applies to |
| **Copy Store Labels** | Yes | Copy store-specific rule name translations |

Set any of these to **No** if you prefer the duplicate to start without those associations, requiring you to assign them explicitly.

### Custom Fields to Reset

Some Magento installations add extra columns to the `salesrule` database table (for example, fields added by custom modules or third-party integrations). By default, these columns are copied verbatim when a rule is duplicated.

The **Custom Fields to Reset on Duplication** table lets you declare which columns should be reset instead:

| Column                                 | Description |
|----------------------------------------|-------------|
| **Field Name (DB Column)**             | The exact column name in the `salesrule` table |
| **Reset Value (leave empty for "NULL")** | The value to set on the duplicate. Leave empty to reset to `NULL`. |

Click **Add Field** to add a row, then **Save Config**.

#### Example

To reset a custom field called `my_custom_field` to `NULL` on every duplication:

| Field Name | Reset Value |
|------------|-------------|
| `my_custom_field` | _(empty)_ |

To reset it to a fixed placeholder value instead:

| Field Name | Reset Value |
|------------|-------------|
| `my_custom_field` | `PENDING` |

#### Overriding the rule name suffix

By default, duplicated rules get `" (Copy)"` appended to their name. You can override this by adding `name` as a custom field reset:

| Field Name | Reset Value |
|------------|-------------|
| `name` | _(empty — sets name to NULL)_ |
| `name` | `My Custom Suffix` |

> **Note:** The reset value replaces the entire name, not just the suffix. If you want a custom suffix you will need to set a static value; dynamic name construction is not supported.

#### Fields that are always reset

The following fields are unconditionally reset on every duplication and **cannot be overridden** by this configuration, even if you add them to the table above:

| Field | Always reset to |
|-------|----------------|
| `rule_id` | _(new auto-generated ID)_ |
| `from_date` | `NULL` |
| `to_date` | `NULL` |
| `coupon_code` | `NULL` |
| `times_used` | `0` |

## Technical Information
