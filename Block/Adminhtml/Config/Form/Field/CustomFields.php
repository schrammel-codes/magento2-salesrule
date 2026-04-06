<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Block\Adminhtml\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class CustomFields extends AbstractFieldArray
{
    protected function _prepareToRender(): void
    {
        $this->addColumn(
            'field_name',
            [
                'label' => __('Field Name (DB Column)'),
                'style' => 'width: 220px',
                'class' => 'required-entry',
            ]
        );

        $this->addColumn(
            'reset_value',
            [
                'label' => __('Reset Value (leave empty for "NULL")'),
                'style' => 'width: 220px',
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = (string)__('Add Field');
    }
}
