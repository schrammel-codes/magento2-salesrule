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
use Magento\SalesRule\Model\RuleRepository;
use Psr\Log\LoggerInterface;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_SalesRule::delete';

    public function __construct(
        Context $context,
        private readonly RuleRepository $ruleRepository,
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

        try {
            $ids = $this->getRequest()->getParam('ids');
            $deletedCount = 0;

            if (!is_array($ids)) {
                return $resultRedirect;
            }

            foreach ($ids as $ruleId) {
                try {
                    $this->ruleRepository->deleteById($ruleId);
                    $deletedCount++;
                } catch (Exception $e) {
                    $this->logger->error(
                        'Failed to delete rule ID ' . $ruleId . ': ' . $e->getMessage()
                    );
                }
            }

            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 rule(s) have been deleted.', $deletedCount)
                );
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Error during mass delete: ' . $e->getMessage(),
                ['exception' => $e]
            );
            $this->messageManager->addErrorMessage(
                __('An error occurred while deleting the rules. Please check the log for details.')
            );
        }

        return $resultRedirect;
    }
}
