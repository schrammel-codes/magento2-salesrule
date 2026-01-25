<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Block\Adminhtml\Promo\Quote\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\GenericButton;

class DuplicateButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $data = [];
        $ruleId = $this->getRuleId();

        if ($ruleId) {
            $data = [
                'label' => __('Duplicate'),
                'class' => 'duplicate',
                'on_click' => sprintf(
                    "location.href = '%s';",
                    $this->getUrl('schrammelcodes_salesrule/promo_quote/duplicate', ['id' => $ruleId])
                ),
                'sort_order' => 15,
            ];
        }

        return $data;
    }
}
