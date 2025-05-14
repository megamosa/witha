<?php
namespace MagoArab\WithoutEmail\Controller\Account;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\Escaper;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Customer register form handling
 */
class CreatePost extends AbstractAccount implements CsrfAwareActionInterface, HttpPostActionInterface
{
    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var Address
     */
    protected $addressHelper;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var RegionInterfaceFactory
     */
    protected $regionDataFactory;

    /**
     * @var AddressInterfaceFactory
     */
    protected $addressDataFactory;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerDataFactory;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var Registration
     */
    protected $registration;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManagement
     * @param Address $addressHelper
     * @param UrlInterface $urlModel
     * @param FormFactory $formFactory
     * @param SubscriberFactory $subscriberFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerUrl $customerUrl
     * @param Registration $registration
     * @param Escaper $escaper
     * @param DataObjectHelper $dataObjectHelper
     * @param AccountRedirect $accountRedirect
     * @param CustomerFactory $customerFactory
     * @param LoggerInterface $logger
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlInterface $urlModel,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        DataObjectHelper $dataObjectHelper,
        AccountRedirect $accountRedirect,
        CustomerFactory $customerFactory,
        LoggerInterface $logger,
        Validator $formKeyValidator = null
    ) {
        $this->session = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->accountManagement = $accountManagement;
        $this->addressHelper = $addressHelper;
        $this->formFactory = $formFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->regionDataFactory = $regionDataFactory;
        $this->addressDataFactory = $addressDataFactory;
        $this->customerDataFactory = $customerDataFactory;
        $this->customerUrl = $customerUrl;
        $this->registration = $registration;
        $this->escaper = $escaper;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->accountRedirect = $accountRedirect;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
        $this->request = $context->getRequest();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->messageManager = $context->getMessageManager();
        $this->formKeyValidator = $formKeyValidator ?: ObjectManager::getInstance()->get(Validator::class);
        parent::__construct($context);
    }

    /**
     * Get cookie manager
     *
     * @return PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(PhpCookieManager::class);
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Get cookie metadata factory
     *
     * @return CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(CookieMetadataFactory::class);
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * Retrieve customer from current session
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function getCustomer()
    {
        return $this->session->getCustomerDataObject();
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        if (!$this->getRequest()->isPost() || !$this->formKeyValidator->validate($this->getRequest())) {
            $resultRedirect->setPath('*/*/create');
            return $resultRedirect;
        }

        try {
            // Process phone number if present
            $phoneNumber = $this->getRequest()->getParam('phone_number');
            if ($phoneNumber) {
                // Clean and format phone number - keep only digits for email generation
                // Clean and format phone number with proper country code
			$whatsappService = $objectManager->get(\MagoArab\WithoutEmail\Helper\WhatsappService::class);
			$formattedPhone = $whatsappService->formatPhoneNumber($phoneNumber);
			$cleanNumber = preg_replace('/\D/', '', $formattedPhone);
                
                // Create email from phone number
                $domain = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
                $domain = parse_url($domain, PHP_URL_HOST);
                $email = $cleanNumber . '@' . $domain;
                
                // Check if customer exists with this email
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($websiteId);
                $customer->loadByEmail($email);
                
                if ($customer->getId()) {
                    $this->messageManager->addError(__('There is already an account with this phone number. Please try another one or login to your account.'));
                    $this->session->setCustomerFormData($this->getRequest()->getPostValue());
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath('*/*/create', ['_secure' => true]);
                }
                
                // Set the generated email
                $this->getRequest()->setParam('email', $email);
            }

            // Get registration data
            $postData = $this->getRequest()->getPostValue();
            $customerData = [];
            foreach (['firstname', 'lastname', 'email', 'password', 'password_confirmation'] as $field) {
                if (isset($postData[$field])) {
                    $customerData[$field] = $postData[$field];
                }
            }

            // Create customer
            $customer = $this->customerDataFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $customer,
                $customerData,
                '\Magento\Customer\Api\Data\CustomerInterface'
            );

            $customer->setDob(!empty($postData['dob']) ? $postData['dob'] : null);
            $customer->setCustomAttribute('phone_number', $phoneNumber);

            $password = $this->getRequest()->getParam('password');
            $customer = $this->accountManagement
                ->createAccount($customer, $password);

            // Handle newsletter subscription
            if ($this->getRequest()->getParam('is_subscribed', false)) {
                $this->subscriberFactory->create()->subscribeCustomerById($customer->getId());
            }

            // Set success message
            $this->messageManager->addSuccess(
                __('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName())
            );

            // Customer authentication
            $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
            if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                $email = $this->customerUrl->getEmailConfirmationUrl($customer->getEmail());
                $this->messageManager->addSuccess(
                    __('You must confirm your account. Please check your email for the confirmation link or <a href="%1">click here</a> for a new link.', $email)
                );
                $url = $this->customerUrl->getAccountUrl();
            } else {
                $this->session->setCustomerDataAsLoggedIn($customer);
                $this->getCookieManager()->deleteCookie('mage-cache-sessid');
                $url = $this->getRedirectUrl();
            }

            return $resultRedirect->setPath($url);

        } catch (AccountException $e) {
            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
            $this->messageManager->addError($e->getMessage());
        } catch (InputException $e) {
            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
            $this->messageManager->addError($this->escaper->escapeHtml($e->getMessage()));
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addError($this->escaper->escapeHtml($error->getMessage()));
            }
        } catch (LocalizedException $e) {
            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
            $this->messageManager->addError($this->escaper->escapeHtml($e->getMessage()));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->session->setCustomerFormData($this->getRequest()->getPostValue());
            $this->messageManager->addException($e, __('Something went wrong while creating your account.'));
        }

        $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        return $resultRedirect->setPath('*/*/create', ['_secure' => true]);
    }

    /**
     * Get redirect url
     *
     * @return string
     */
    private function getRedirectUrl()
    {
        $redirectUrl = $this->accountRedirect->getRedirectCookie();
        if (!$this->getScopeConfig()->getValue('customer/startup/redirect_dashboard') && $redirectUrl) {
            $this->accountRedirect->clearRedirectCookie();
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->_redirect->success($redirectUrl));
            return $redirectUrl;
        }
        return $this->accountRedirect->getRedirectUrl();
    }

    /**
     * Get scope config
     *
     * @return ScopeConfigInterface
     */
    private function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will do.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}