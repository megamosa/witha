<?php
namespace MagoArab\WithoutEmail\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use MagoArab\WithoutEmail\Helper\Data as HelperData;

class ForgotPasswordPost extends AbstractAccount implements HttpPostActionInterface
{
    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var Session
     */
    protected $session;
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;
    
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CustomerCollectionFactory $customerCollectionFactory,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper = $escaper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->helperData = $helperData;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        // Check if hybrid mode is enabled
        $isHybridMode = $this->helperData->isHybridModeEnabled();
        $isPhoneOnlyMode = $this->helperData->isPhoneOnlyMode();
        
        if ($isHybridMode) {
            $recoveryMethod = $this->getRequest()->getPost('recovery_method');
            
            if ($recoveryMethod === 'phone') {
                // Phone recovery is handled via AJAX in the JS
                // Just ensure we redirect back to the forgot password page
                return $resultRedirect->setPath('*/*/forgotpassword');
            }
            
            // If recovery method is email, continue with standard flow
            $username = (string)$this->getRequest()->getPost('email');
        } else {
            // Standard mode or Phone only mode
            $username = (string)$this->getRequest()->getPost('email');
        }
        
        if ($username) {
            try {
                // Check if username is a phone number
                if (preg_match('/^\+?[0-9]+$/', str_replace(' ', '', $username)) || 
                    (strlen($username) > 0 && $isPhoneOnlyMode)) {
                    // This is a phone number - we need to find the associated email
                    $cleanPhone = preg_replace('/\D/', '', $username);
                    $domain = $this->storeManager->getStore()->getBaseUrl();
                    $domainParts = parse_url($domain);
                    $hostName = $domainParts['host'];
                    
                    // Look for user with this phone attribute
                    $collection = $this->customerCollectionFactory->create();
                    $collection->addAttributeToSelect('email');
                    $collection->addAttributeToFilter('phone_number', ['like' => '%' . $cleanPhone . '%']);
                    
                    if ($collection->getSize() > 0) {
                        $customer = $collection->getFirstItem();
                        $email = $customer->getEmail();
                    } else {
                        // Try auto-generated email format 
                        $email = $cleanPhone . '@' . $hostName;
                    }
                } else {
                    // Treat as regular email
                    $email = $username;
                }
                
                // Reset password with the found or provided email
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
                
                $this->messageManager->addSuccessMessage(
                    __('If there is an account associated with %1 you will receive an email with a link to reset your password.', $this->escaper->escapeHtml($username))
                );
                
                return $resultRedirect->setPath('*/*/');
            } catch (NoSuchEntityException $exception) {
                // Do nothing, we don't want to give away whether a username is valid
            } catch (SecurityViolationException $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
                return $resultRedirect->setPath('*/*/forgotpassword');
            } catch (\Exception $exception) {
                $this->messageManager->addExceptionMessage(
                    $exception,
                    __('There was an error resetting your password.')
                );
                return $resultRedirect->setPath('*/*/forgotpassword');
            }
            
            $this->messageManager->addSuccessMessage(
                __('If there is an account associated with %1 you will receive an email with a link to reset your password.', $this->escaper->escapeHtml($username))
            );
            return $resultRedirect->setPath('*/*/');
        } else {
            $this->messageManager->addErrorMessage(__('Please enter your email address or phone number.'));
            return $resultRedirect->setPath('*/*/forgotpassword');
        }
    }
}