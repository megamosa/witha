<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Plugin\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AccountManagement as Subject;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Store\Model\StoreManagerInterface;
use MagoArab\WithoutEmail\Helper\Config;
use MagoArab\WithoutEmail\Model\PhoneNumber;

class AccountManagement
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StringUtils
     */
    protected $stringUtils;

    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var PhoneNumber
     */
    protected $phoneNumber;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param StringUtils $stringUtils
     * @param Config $configHelper
     * @param PhoneNumber $phoneNumber
     */
    public function __construct(
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        StringUtils $stringUtils,
        Config $configHelper,
        PhoneNumber $phoneNumber
    ) {
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->stringUtils = $stringUtils;
        $this->configHelper = $configHelper;
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * Before create customer account
     *
     * @param Subject $subject
     * @param CustomerInterface $customer
     * @param string|null $password
     * @param string|null $redirectUrl
     * @return array
     * @throws LocalizedException
     */
public function beforeCreateAccount(
    Subject $subject,
    CustomerInterface $customer,
    $password = null,
    $redirectUrl = null
) {
    if (!$this->configHelper->isEnabled()) {
        return [$customer, $password, $redirectUrl];
    }

    // Check if hybrid mode is enabled
    $isHybridMode = $this->configHelper->isHybridMode();
    
    // Get phone number from request or custom attribute
    $phoneNumber = $this->getPhoneNumber($customer);
    
    // Get email directly
    $email = $customer->getEmail();
    
    // In hybrid mode, allow email-based registration if email is provided
    if ($isHybridMode && !empty($email) && strpos($email, '@') !== false) {
        // Email is provided and valid - allow to proceed without phone number
        if (!empty($phoneNumber)) {
            // Both email and phone provided - still set the phone attribute
            $this->phoneNumber->setPhoneNumber($phoneNumber);
            if (!$this->phoneNumber->validatePhoneNumber()) {
                throw new LocalizedException(__(
                    'Phone number must be between %1 and %2 digits.',
                    $this->configHelper->getMinPhoneLength(),
                    $this->configHelper->getMaxPhoneLength()
                ));
            }
        }
        
        return [$customer, $password, $redirectUrl];
    }
    
    // Phone-only or hybrid mode with no valid email
    if (empty($phoneNumber)) {
        throw new LocalizedException(__('Phone number is required.'));
    }
    
    // Validate phone number
    $this->phoneNumber->setPhoneNumber($phoneNumber);
    if (!$this->phoneNumber->validatePhoneNumber()) {
        throw new LocalizedException(__(
            'Phone number must be between %1 and %2 digits.',
            $this->configHelper->getMinPhoneLength(),
            $this->configHelper->getMaxPhoneLength()
        ));
    }
    
    // Generate email from phone number
    $domain = $this->getDomainFromStore();
    $generatedEmail = $this->phoneNumber->generateEmailFromPhone($phoneNumber, $domain);
    
    // Set generated email to customer
    $customer->setEmail($generatedEmail);
    
    return [$customer, $password, $redirectUrl];
}

    /**
     * Get phone number from customer
     *
     * @param CustomerInterface $customer
     * @return string|null
     */
    protected function getPhoneNumber(CustomerInterface $customer): ?string
    {
        $customAttribute = $customer->getCustomAttribute('phone_number');
        
        if ($customAttribute) {
            return $customAttribute->getValue();
        }
        
        // Try to get from request if not set in customer
        return $this->request->getParam('phone_number');
    }

    /**
     * Get domain from current store
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getDomainFromStore(): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $parsedUrl = parse_url($baseUrl);
        
        return $parsedUrl['host'] ?? 'example.com';
    }

    /**
     * Before authenticate with phone
     *
     * @param Subject $subject
     * @param string $username
     * @param string $password
     * @return array
     */
    public function beforeAuthenticate(
        Subject $subject,
        $username,
        $password
    ) {
        if (!$this->configHelper->isEnabled()) {
            return [$username, $password];
        }
        
        // Check if username is a phone number
        if (is_numeric($username) || (substr($username, 0, 1) === '+' && is_numeric(substr($username, 1)))) {
            // It's a phone number, convert to email
            $domain = $this->getDomainFromStore();
            
            // Clean the phone number
            $phoneNumber = preg_replace('/\D/', '', $username);
            
            $email = $this->phoneNumber->generateEmailFromPhone($phoneNumber, $domain);
            
            return [$email, $password];
        }
        
        return [$username, $password];
    }
}