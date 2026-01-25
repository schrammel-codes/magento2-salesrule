<?php
declare(strict_types=1);

namespace SchrammelCodes\SalesRule\Test\Unit\Controller\Adminhtml\Promo\Quote;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
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
use SchrammelCodes\SalesRule\Controller\Adminhtml\Promo\Quote\MassDuplicate;

class MassDuplicateTest extends TestCase
{
    private MassDuplicate $controller;
    private Context|MockObject $context;
    private Registry|MockObject $coreRegistry;
    private FileFactory|MockObject $fileFactory;
    private Date|MockObject $dateFilter;
    private RuleFactory|MockObject $ruleFactory;
    private RuleResource|MockObject $ruleResource;
    private RuleDuplicatorInterface|MockObject $ruleDuplicator;
    private LoggerInterface|MockObject $logger;
    private RequestInterface|MockObject $request;
    private ManagerInterface|MockObject $messageManager;
    private ResultFactory|MockObject $resultFactory;
    private Redirect|MockObject $resultRedirect;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->coreRegistry = $this->createMock(Registry::class);
        $this->fileFactory = $this->createMock(FileFactory::class);
        $this->dateFilter = $this->createMock(Date::class);
        $this->ruleFactory = $this->createMock(RuleFactory::class);
        $this->ruleResource = $this->createMock(RuleResource::class);
        $this->ruleDuplicator = $this->createMock(RuleDuplicatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->resultRedirect = $this->createMock(Redirect::class);

        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->method('getResultFactory')->willReturn($this->resultFactory);

        $this->resultFactory->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->resultRedirect);

        $this->resultRedirect->method('setPath')->willReturnSelf();

        $this->controller = new MassDuplicate(
            $this->context,
            $this->coreRegistry,
            $this->fileFactory,
            $this->dateFilter,
            $this->ruleFactory,
            $this->ruleResource,
            $this->ruleDuplicator,
            $this->logger
        );
    }

    public function testExecuteSuccessfulDuplication(): void
    {
        $ids = [1, 2];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn($ids);

        $rule1 = $this->createMock(Rule::class);
        $rule2 = $this->createMock(Rule::class);

        $this->ruleFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $this->ruleResource->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive([$rule1, 1], [$rule2, 2])
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $this->ruleDuplicator->expects($this->exactly(2))
            ->method('duplicate')
            ->withConsecutive([$rule1], [$rule2]);

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 record(s) have been duplicated.', 2));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteWithPartialFailure(): void
    {
        $ids = [1, 2];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn($ids);

        $rule1 = $this->createMock(Rule::class);
        $rule2 = $this->createMock(Rule::class);

        $this->ruleFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $this->ruleResource->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive([$rule1, 1], [$rule2, 2])
            ->willReturnOnConsecutiveCalls($rule1, $rule2);

        $this->ruleDuplicator->expects($this->exactly(2))
            ->method('duplicate')
            ->willReturnCallback(function ($rule) use ($rule1) {
                if ($rule === $rule1) {
                    throw new Exception('Duplication failed');
                }

                return $this->createMock(Rule::class);
            });

        $this->logger->expects($this->once())
            ->method('error');

        $this->messageManager->expects($this->once())
            ->method('addSuccessMessage')
            ->with(__('A total of %1 record(s) have been duplicated.', 1));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }

    public function testExecuteWithNoSuccesses(): void
    {
        $ids = [1];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn($ids);

        $rule1 = $this->createMock(Rule::class);

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($rule1);

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->with($rule1, 1)
            ->willReturn($rule1);

        $this->ruleDuplicator->expects($this->once())
            ->method('duplicate')
            ->willThrowException(new Exception('Duplication failed'));

        $this->logger->expects($this->once())
            ->method('error');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('No rules were duplicated.'));

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

    public function testExecuteWithGeneralException(): void
    {
        $ids = [1];

        $this->request->expects($this->once())
            ->method('getParam')
            ->with('ids')
            ->willReturn($ids);

        $rule = $this->createMock(Rule::class);

        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($rule);

        $this->ruleResource->expects($this->once())
            ->method('load')
            ->willThrowException(new Exception('Database error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to duplicate rule ID 1: Database error');

        $this->messageManager->expects($this->once())
            ->method('addErrorMessage')
            ->with(__('No rules were duplicated.'));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales_rule/*/')
            ->willReturnSelf();

        $result = $this->controller->execute();

        $this->assertSame($this->resultRedirect, $result);
    }
}
