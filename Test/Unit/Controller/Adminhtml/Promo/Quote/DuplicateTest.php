<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Controller\Adminhtml\Promo\Quote;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SchrammelCodes\SalesRule\Api\RuleDuplicatorInterface;
use SchrammelCodes\SalesRule\Controller\Adminhtml\Promo\Quote\Duplicate;

class DuplicateTest extends TestCase
{
    private Duplicate $controller;
    private Context|MockObject $context;
    private Registry|MockObject $coreRegistry;
    private FileFactory|MockObject $fileFactory;
    private Date|MockObject $dateFilter;
    private RuleResource|MockObject $ruleResource;
    private RuleFactory|MockObject $ruleFactory;
    private RuleDuplicatorInterface|MockObject $ruleDuplicator;
    private LoggerInterface|MockObject $logger;
    private RequestInterface|MockObject $request;
    private ManagerInterface|MockObject $messageManager;
    private RedirectFactory|MockObject $resultRedirectFactory;
    private Redirect|MockObject $resultRedirect;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->coreRegistry = $this->createMock(Registry::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->dateFilter = $this->createMock(Date::class);
        $this->ruleResource = $this->createMock(RuleResource::class);
        $this->ruleFactory = $this->createMock(RuleFactory::class);
        $this->ruleDuplicator = $this->createMock(RuleDuplicatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->resultRedirect = $this->createMock(Redirect::class);

        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->method('getResultRedirectFactory')->willReturn($this->resultRedirectFactory);

        $this->resultRedirectFactory->method('create')->willReturn($this->resultRedirect);
        $this->resultRedirect->method('setPath')->willReturnSelf();

        $this->controller = new Duplicate(
            $this->ruleResource,
            $this->ruleFactory,
            $this->ruleDuplicator,
            $this->logger,
            $this->context,
            $this->coreRegistry,
            $this->fileFactory,
            $this->dateFilter
        );
    }

    public function testExecuteWithMissingId(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn(null);

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('We can\'t find a rule to duplicate.'));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteSuccessfulDuplication(): void
    {
        $ruleId = 123;
        $newRuleId = 456;

        $originalRule = $this->createMock(Rule::class);
        $originalRule->method('getId')->willReturn($ruleId);

        $newRule = $this->createMock(Rule::class);
        $newRule->method('getId')->willReturn($newRuleId);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($ruleId);

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($originalRule);

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->with($originalRule, $ruleId)
            ->willReturn($originalRule);

        $this->ruleDuplicator->expects($this->once())
            ->method('duplicate')
            ->with($originalRule)
            ->willReturn($newRule);

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('The rule has been duplicated. New rule ID: %1', $newRuleId));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/edit', ['id' => $newRuleId])
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteRuleNotFound(): void
    {
        $ruleId = 123;

        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn(null);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($ruleId);

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($rule);

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->with($rule, $ruleId)
            ->willReturn($rule);

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('This rule no longer exists.'));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteWithLocalizedException(): void
    {
        $ruleId = 123;
        $exceptionMessage = 'Duplication failed';

        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn($ruleId);

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($ruleId);

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($rule);

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->with($rule, $ruleId)
            ->willReturn($rule);

        $this->ruleDuplicator->expects($this->once())
            ->method('duplicate')
            ->willThrowException(new LocalizedException(__($exceptionMessage)));

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteWithGeneralException(): void
    {
        $ruleId = 123;

        $rule = $this->createMock(Rule::class);
        $rule->method('getId')->willReturn($ruleId);

        $exception = new \Exception('Unexpected error');

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($ruleId);

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($rule);

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->with($rule, $ruleId)
            ->willReturn($rule);

        $this->ruleDuplicator->expects($this->once())
            ->method('duplicate')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($exception);

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('An error occurred while duplicating the rule. Please check the logs.'));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }
}
