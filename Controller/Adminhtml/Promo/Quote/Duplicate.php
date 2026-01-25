<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Controller\Adminhtml\Promo\Quote;

use Exception;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Controller\Adminhtml\Promo\Quote;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\SalesRule\Model\RuleFactory;
use SchrammelCodes\SalesRule\Api\RuleDuplicatorInterface;
use Psr\Log\LoggerInterface;

class Duplicate extends Quote implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'SchrammelCodes_SalesRule::quote_duplicate';

    public function __construct(
        private readonly RuleResource $ruleResource,
        private readonly RuleFactory $ruleFactory,
        private readonly RuleDuplicatorInterface $ruleDuplicator,
        private readonly LoggerInterface $logger,
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        Date $dateFilter
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('We can\'t find a rule to duplicate.'));

            return $resultRedirect->setPath('sales_rule/*/');
        }

        try {
            $originalRule = $this->ruleFactory->create();
            $this->ruleResource->load($originalRule, $id);

            if (!$originalRule->getId()) {
                throw new NoSuchEntityException(__('This rule no longer exists.'));
            }

            $newRule = $this->ruleDuplicator->duplicate($originalRule);

            $this->messageManager->addSuccessMessage(
                __('The rule has been duplicated. New rule ID: %1', $newRule->getId())
            );

            return $resultRedirect->setPath('sales_rule/*/edit', ['id' => $newRule->getId()]);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This rule no longer exists.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while duplicating the rule. Please check the logs.')
            );
            $this->logger->critical($e);
        }

        return $resultRedirect->setPath('sales_rule/*/');
    }
}
