<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Controller\Adminhtml\Promo\Quote;

use Exception;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\SalesRule\Controller\Adminhtml\Promo\Quote;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use SchrammelCodes\SalesRule\Api\RuleDuplicatorInterface;
use Psr\Log\LoggerInterface;

class MassDuplicate extends Quote implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'SchrammelCodes_SalesRule::quote_duplicate';

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        Date $dateFilter,
        private readonly RuleFactory $ruleFactory,
        private readonly RuleResource $ruleResource,
        private readonly RuleDuplicatorInterface $ruleDuplicator,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter);
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
            $duplicatedCount = 0;

            if (!is_array($ids)) {
                return $resultRedirect;
            }

            foreach ($ids as $ruleId) {
                try {
                    $rule = $this->ruleFactory->create();
                    $this->ruleResource->load($rule, $ruleId);
                    $this->ruleDuplicator->duplicate($rule);
                    $duplicatedCount++;
                } catch (Exception $e) {
                    $this->logger->error(
                        'Failed to duplicate rule ID ' . $ruleId . ': ' . $e->getMessage()
                    );
                }
            }

            if ($duplicatedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 record(s) have been duplicated.', $duplicatedCount)
                );
            } else {
                $this->messageManager->addErrorMessage(__('No rules were duplicated.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while duplicating rules. Please check the logs.')
            );
            $this->logger->critical($e);
        }

        return $resultRedirect;

    }
}
