<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Controller\Adminhtml\Promo\Quote;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\SalesRule\Model\RuleRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SchrammelCodes\SalesRule\Controller\Adminhtml\Promo\Quote\MassDelete;

class MassDeleteTest extends TestCase
{
    private MassDelete $controller;
    private RuleRepository|MockObject $ruleRepository;
    private ManagerInterface|MockObject $messageManager;
    private ResultFactory|MockObject $resultFactory;
    private LoggerInterface|MockObject $logger;
    private Context|MockObject $context;
    private RequestInterface|MockObject $request;
    private Redirect|MockObject $resultRedirect;

    protected function setUp(): void
    {
        $this->ruleRepository = $this->createMock(RuleRepository::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->resultRedirect = $this->createMock(Redirect::class);

        $this->context = $this->createMock(Context::class);
        $this->context->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->method('getResultFactory')->willReturn($this->resultFactory);
        $this->context->method('getRequest')->willReturn($this->request);

        $this->resultFactory->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->method('setPath')->willReturnSelf();

        $this->controller = new MassDelete(
            $this->context,
            $this->ruleRepository,
            $this->logger
        );
    }

    public function testExecuteDeletesRulesSuccessfully(): void
    {
        $ids = [1, 2];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn($ids);

        $this->ruleRepository->expects($this->exactly(2))
            ->method('deleteById')
            ->willReturnCallback(function ($id) {
                static $callCount = 0;
                $callCount++;
                $this->assertContains($id, [1, 2]);

                return true;
            });

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 rule(s) have been deleted.', 2));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteHandlesPartialFailure(): void
    {
        $ids = [1, 2];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn($ids);

        $exception = new Exception('Delete failed');

        $this->ruleRepository->expects($this->exactly(2))
            ->method('deleteById')
            ->willReturnCallback(function ($id) use ($exception) {
                if ($id === 2) {
                    throw $exception;
                }

                return true;
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to delete rule ID 2: Delete failed');

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 rule(s) have been deleted.', 1));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteWithNoIds(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn(null);

        $this->ruleRepository->expects($this->never())
            ->method('deleteById');

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

    public function testExecuteHandlesCompleteFailure(): void
    {
        $ids = [1];
        $exception = new Exception('Delete failed');

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn($ids);

        $this->ruleRepository->expects($this->once())
            ->method('deleteById')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to delete rule ID 1: Delete failed');

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
