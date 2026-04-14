define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            // Disable the two-way link so writing to value() does not propagate
            // back to the source data and get submitted with the form.
            links: {
                value: ''
            }
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            this.formatAndShow();
            return this;
        },

        /**
         * Read duplicated_from once from the source, format the display value,
         * and toggle visibility. Runs synchronously on init — no listeners needed.
         *
         * On Open Source: duplicated_from holds the rule_id → "Rule ID #42"
         * On Commerce:    duplicated_from holds the row_id, and duplicated_from_rule_id
         *                 is populated by the DataProvider plugin → "Rule ID #42 (Row ID #50)"
         */
        formatAndShow: function () {
            var data = this.source.get('data'),
                rawId = data && data.duplicated_from,
                ruleId, storedIdentifier, displayValue;

            if (!rawId) {
                this.visible(false);
                return;
            }

            ruleId = data.duplicated_from_rule_id;
            storedIdentifier = data.stored_identifier_field === 'rule_id' ? 'Rule' : 'Row';
            displayValue = ruleId
                ? 'Rule ID #' + ruleId + ' (Row ID #' + rawId + ')'
                : storedIdentifier + ' ID #' + rawId;

            this.initialValue = displayValue;
            this.value(displayValue);
            this.visible(true);
        }
    });
});
