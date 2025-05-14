<?php
namespace MagoArab\WithoutEmail\Controller\Adminhtml\Templates;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;

class Save extends Action
{
    /**
     * @var WriterInterface
     */
    protected $configWriter;
    
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;
    
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->messageManager = $messageManager;
    }

    /**
     * Save templates
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        try {
            $templates = $this->getRequest()->getParam('templates', []);
            
            foreach ($templates as $status => $template) {
                $this->configWriter->save(
                    "magoarab_withoutemail/notifications/template_{$status}",
                    $template,
                    \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
            }
            
            // Clear config cache
            $this->cacheTypeList->cleanType('config');
            
            $this->messageManager->addSuccessMessage(__('Templates have been saved successfully.'));
            
            return $resultRedirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error saving templates: %1', $e->getMessage()));
            return $resultRedirect->setPath('*/*/index');
        }
    }
}