<?php
namespace MagoArab\WithoutEmail\Controller\Account;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use MagoArab\WithoutEmail\Helper\Config;
use Psr\Log\LoggerInterface;

class ResetPasswordPost implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        AccountManagementInterface $accountManagement,
        Config $configHelper,
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->accountManagement = $accountManagement;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $phoneNumber = $this->request->getParam('phone_number');
            $newPassword = $this->request->getParam('new_password');
            $confirmPassword = $this->request->getParam('confirm_password');
            
            if (!$phoneNumber || !$newPassword || !$confirmPassword) {
                throw new LocalizedException(__('Please fill all required fields.'));
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new LocalizedException(__('Passwords do not match.'));
            }
            
            // Check if OTP is verified
            $otpData = $this->customerSession->getData('otp_' . $phoneNumber);
            if (!$otpData || !isset($otpData['verified']) || !$otpData['verified']) {
                throw new LocalizedException(__('Please verify your phone number first.'));
            }
            
            // Find customer by phone number
            $customer = $this->findCustomerByPhone($phoneNumber);
            if (!$customer) {
                throw new LocalizedException(__('No account found with this phone number.'));
            }
            
            // Reset password using email
            $email = $customer->getEmail();
            $this->accountManagement->changePassword($email, $customer->getEmail(), $newPassword);
            
            // Clear OTP session data
            $this->customerSession->unsetData('otp_' . $phoneNumber);
            
            return $resultJson->setData([
                'success' => true,
                'message' => __('Password has been successfully reset. You can now login with your new password.')
            ]);
            
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Password reset error: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while resetting password. Please try again.')
            ]);
        }
    }
    
    /**
     * Find customer by phone number
     *
     * @param string $phoneNumber
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    protected function findCustomerByPhone(string $phoneNumber)
    {
        try {
            // First, try searching by generated email
            $domain = $this->getDomainFromStore();
            $generatedEmail = $phoneNumber . '@' . $domain;
            
            try {
                $customer = $this->customerRepository->get($generatedEmail);
                return $customer;
            } catch (\Exception $e) {
                // Customer not found by email, try by phone attribute
            }
            
            // If not found by email, search by custom attribute
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('custom_attribute', $phoneNumber, 'eq')
                ->create();
                
            $searchResults = $this->customerRepository->getList($searchCriteria);
            
            if ($searchResults->getTotalCount() > 0) {
                $items = $searchResults->getItems();
                return reset($items);
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Error finding customer: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get domain from store
     *
     * @return string
     */
    protected function getDomainFromStore(): string
    {
        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $parsedUrl = parse_url($baseUrl);
            return $parsedUrl['host'] ?? 'example.com';
        } catch (\Exception $e) {
            return 'example.com';
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}