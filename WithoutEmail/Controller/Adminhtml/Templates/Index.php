<?php
namespace MagoArab\WithoutEmail\Controller\Adminhtml\Templates;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * WhatsApp Templates index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('MagoArab_WithoutEmail::templates');
        $resultPage->addBreadcrumb(__('WhatsApp'), __('WhatsApp'));
        $resultPage->addBreadcrumb(__('Templates'), __('Templates'));
        $resultPage->getConfig()->getTitle()->prepend(__('WhatsApp Templates'));
        
        return $resultPage;
    }
}