<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Controller\Adminhtml\Promo\Quote;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use Magento\SalesRule\Model\RuleFactory;
use Psr\Log\LoggerInterface;

class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_SalesRule::quote';

    public function __construct(
        Context $context,
        private readonly RuleFactory $ruleFactory,
        private readonly RuleResource $ruleResource,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('sales_rule/*/');

        $ids = $this->getRequest()->getParam('ids');
        $status = $this->getRequest()->getParam('status');

        if (!is_array($ids)) {
            return $resultRedirect;
        }

        if ($status === null) {
            $this->messageManager->addErrorMessage(__('Please select a status.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            return $resultRedirect->setPath('sales_rule/*/');
        }

        try {
            $updatedCount = 0;

            foreach ($ids as $ruleId) {
                try {
                    $rule = $this->ruleFactory->create();
                    $this->ruleResource->load($rule, $ruleId);
                    $rule->setIsActive((bool) $status);
                    $this->ruleResource->save($rule);
                    $updatedCount++;
                } catch (Exception $e) {
                    $this->logger->error(
                        'Failed to update rule ID ' . $rule->getId() . ': ' . $e->getMessage()
                    );
                }
            }

            if ($updatedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 rule(s) have been updated.', $updatedCount)
                );
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Error during mass status update: ' . $e->getMessage(),
                ['exception' => $e]
            );
            $this->messageManager->addErrorMessage(
                __('An error occurred while updating the rules. Please check the log for details.')
            );
        }

        return $resultRedirect;
    }
}
