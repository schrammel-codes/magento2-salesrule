<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DuplicateActiveStatus implements OptionSourceInterface
{
    public const DISABLED = 0;
    public const ENABLED = 1;
    public const KEEP = 2;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::KEEP,     'label' => __('Keep as is')],
            ['value' => self::DISABLED, 'label' => __('Disabled')],
            ['value' => self::ENABLED,  'label' => __('Enabled')],
        ];
    }
}
