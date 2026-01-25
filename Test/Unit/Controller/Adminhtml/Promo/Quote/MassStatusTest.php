<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Controller\Adminhtml\Promo\Quote;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SchrammelCodes\SalesRule\Controller\Adminhtml\Promo\Quote\MassStatus;

class MassStatusTest extends TestCase
{
    private MassStatus $controller;
    private RuleFactory|MockObject $ruleFactory;
    private RuleResource|MockObject $ruleResource;
    private ManagerInterface|MockObject $messageManager;
    private ResultFactory|MockObject $resultFactory;
    private RequestInterface|MockObject $request;
    private LoggerInterface|MockObject $logger;
    private Context|MockObject $context;
    private Redirect|MockObject $resultRedirect;

    protected function setUp(): void
    {
        $this->ruleFactory = $this->createMock(RuleFactory::class);
        $this->ruleResource = $this->createMock(RuleResource::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resultRedirect = $this->createMock(Redirect::class);

        $this->context = $this->createMock(Context::class);
        $this->context->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->method('getResultFactory')->willReturn($this->resultFactory);
        $this->context->method('getRequest')->willReturn($this->request);

        $this->resultFactory->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->method('setPath')->willReturnSelf();

        $this->controller = new MassStatus(
            $this->context,
            $this->ruleFactory,
            $this->ruleResource,
            $this->logger
        );
    }

    public function testExecuteUpdatesRulesStatusSuccessfully(): void
    {
        $ids = [1, 2];

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['ids', null, $ids],
                ['status', null, '1'],
            ]);

        $rule1 = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsActive'])
            ->getMock();
        $rule2 = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsActive'])
            ->getMock();

        $this->ruleFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $this->ruleResource->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive([$rule1, 1], [$rule2, 2])
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $rule1->expects($this->once())
            ->method('setIsActive')
            ->with(true)
            ->willReturnSelf();

        $rule2->expects($this->once())
            ->method('setIsActive')
            ->with(true)
            ->willReturnSelf();

        $this->ruleResource->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($rule) use ($rule1, $rule2) {
                $this->assertContains($rule, [$rule1, $rule2]);

                return $rule;
            });

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 rule(s) have been updated.', 2));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteHandlesMissingStatusParameter(): void
    {
        $ids = [1];

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['ids', null, $ids],
                ['status', null, null],
            ]);

        $this->ruleFactory->expects($this->never())
            ->method('create');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('Please select a status.'));

        $this->resultFactory->expects($this->exactly(2))
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->expects($this->exactly(2))
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteHandlesPartialFailure(): void
    {
        $ids = [1, 2];

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['ids', null, $ids],
                ['status', null, '0'],
            ]);

        $rule1 = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsActive'])
            ->onlyMethods(['getId'])
            ->getMock();
        $rule2 = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsActive'])
            ->onlyMethods(['getId'])
            ->getMock();

        $rule1->method('getId')->willReturn(1);
        $rule2->method('getId')->willReturn(2);

        $this->ruleFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $this->ruleResource->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive([$rule1, 1], [$rule2, 2])
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $rule1->expects($this->once())
            ->method('setIsActive')
            ->with(false)
            ->willReturnSelf();

        $rule2->expects($this->once())
            ->method('setIsActive')
            ->with(false)
            ->willReturnSelf();

        $exception = new Exception('Save failed');

        $this->ruleResource->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($rule) use ($rule1, $rule2, $exception) {
                if ($rule === $rule2) {
                    throw $exception;
                }

                return $rule;
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to update rule ID 2: Save failed');

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 rule(s) have been updated.', 1));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteHandlesCompleteFailure(): void
    {
        $ids = [1];
        $exception = new Exception('Load failed');

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['ids', null, $ids],
                ['status', null, '1'],
            ]);

        $rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();

        $rule->method('getId')->willReturn(1);

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($rule);

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to update rule ID 1: Load failed');

        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage');

        $this->messageManager->expects($this->never())
            ->method('addErrorMessage');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteWithNoIds(): void
    {
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap([
                ['ids', null, null],
                ['status', null, '1'],
            ]);

        $this->ruleFactory->expects($this->never())
            ->method('create');

        $this->messageManager->expects($this->never())
            ->method('addSuccessMessage');

        $this->messageManager->expects($this->never())
            ->method('addErrorMessage');

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }
}
