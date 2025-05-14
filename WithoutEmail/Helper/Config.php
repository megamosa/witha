<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config extends AbstractHelper
{
    /**
     * Config paths
     */
	 // Add these constants at the top
	const XML_PATH_TWILIO_ACCOUNT_SID = 'magoarab_withoutemail/whatsapp_settings/twilio_account_sid';
	const XML_PATH_TWILIO_AUTH_TOKEN = 'magoarab_withoutemail/whatsapp_settings/twilio_auth_token';
	const XML_PATH_TWILIO_WHATSAPP_NUMBER = 'magoarab_withoutemail/whatsapp_settings/twilio_whatsapp_number';
	const XML_PATH_WATI_API_KEY = 'magoarab_withoutemail/whatsapp_settings/wati_api_key';
	const XML_PATH_WATI_ENDPOINT = 'magoarab_withoutemail/whatsapp_settings/wati_endpoint';
    const XML_PATH_ENABLED = 'magoarab_withoutemail/general/enabled';
    const XML_PATH_MIN_PHONE_LENGTH = 'magoarab_withoutemail/general/min_phone_length';
    const XML_PATH_MAX_PHONE_LENGTH = 'magoarab_withoutemail/general/max_phone_length';
    const XML_PATH_WHATSAPP_PROVIDER = 'magoarab_withoutemail/whatsapp_settings/provider';
    const XML_PATH_ULTRAMSG_API_KEY = 'magoarab_withoutemail/whatsapp_settings/ultramsg_api_key';
    const XML_PATH_ULTRAMSG_INSTANCE_ID = 'magoarab_withoutemail/whatsapp_settings/ultramsg_instance_id';
    const XML_PATH_DIALOG360_API_KEY = 'magoarab_withoutemail/whatsapp_settings/dialog360_api_key';
    const XML_PATH_DIALOG360_PHONE_NUMBER_ID = 'magoarab_withoutemail/whatsapp_settings/dialog360_phone_number_id';
    const XML_PATH_OTP_LENGTH = 'magoarab_withoutemail/whatsapp_settings/otp_length';
    const XML_PATH_OTP_EXPIRY = 'magoarab_withoutemail/whatsapp_settings/otp_expiry';
    const XML_PATH_ENABLE_ORDER_NOTIFICATIONS = 'magoarab_withoutemail/notifications/enable_order_notifications';
    const XML_PATH_ORDER_PROCESSING_TEMPLATE = 'magoarab_withoutemail/notifications/order_processing_template';
    const XML_PATH_ORDER_SHIPPED_TEMPLATE = 'magoarab_withoutemail/notifications/order_shipped_template';
    const XML_PATH_ORDER_DELIVERED_TEMPLATE = 'magoarab_withoutemail/notifications/order_delivered_template';
	const XML_PATH_ENABLE_OTP = 'magoarab_withoutemail/whatsapp_settings/enable_otp';
	const XML_PATH_PHONE_FORMAT = 'magoarab_withoutemail/general/phone_format';
	const XML_PATH_ORDER_STATUSES = 'magoarab_withoutemail/notifications/order_statuses';
    const XML_PATH_BUSINESS_NAME = 'magoarab_withoutemail/notifications/business_name';
    const XML_PATH_SUPPORT_PHONE = 'magoarab_withoutemail/notifications/support_phone';
    const XML_PATH_NOTIFICATION_LANGUAGE = 'magoarab_withoutemail/notifications/notification_language';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    /**
     * Get default country
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDefaultCountry(int $storeId = null): string
    {
        $countryCode = $this->scopeConfig->getValue(
            'general/country/default',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        return $countryCode ?: 'EG';
    }

    /**
     * Get preferred countries
     *
     * @param int|null $storeId
     * @return array
     */
    public function getPreferredCountries(int $storeId = null): array
    {
        $default = $this->getDefaultCountry($storeId);
        $countries = [$default, 'US', 'GB', 'SA', 'AE'];
        
        $specificCountriesValue = $this->scopeConfig->getValue(
            'general/country/allow',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        
        if (!empty($specificCountriesValue)) {
            $specificCountries = explode(',', $specificCountriesValue);
            // Make sure default country is first, then add up to 3 more countries
            $result = [$default];
            foreach ($specificCountries as $country) {
                if ($country !== $default && count($result) < 4) {
                    $result[] = $country;
                }
            }
            return $result;
        }
        
        return $countries;
    } 
    /**
     * Get minimum phone length
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMinPhoneLength(int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MIN_PHONE_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 9; // Default minimum length if not set
    }

    /**
     * Get maximum phone length
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxPhoneLength(int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_PHONE_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 15; // Default maximum length if not set
    }

    /**
     * Get WhatsApp provider
     *
     * @param int|null $storeId
     * @return string
     */
    public function getWhatsAppProvider(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_PROVIDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get UltraMsg API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getUltraMsgApiKey(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ULTRAMSG_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get UltraMsg instance ID
     *
     * @param int|null $storeId
     * @return string
     */
    public function getUltraMsgInstanceId(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ULTRAMSG_INSTANCE_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get 360Dialog API key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDialog360ApiKey(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_DIALOG360_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get 360Dialog phone number ID
     *
     * @param int|null $storeId
     * @return string
     */
    public function getDialog360PhoneNumberId(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_DIALOG360_PHONE_NUMBER_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get OTP length
     *
     * @param int|null $storeId
     * @return int
     */
    public function getOtpLength(int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_OTP_LENGTH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 6; // Default OTP length if not set
    }

    /**
     * Get OTP expiry time in minutes
     *
     * @param int|null $storeId
     * @return int
     */
    public function getOtpExpiry(int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_OTP_EXPIRY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 10; // Default expiry time in minutes if not set
    }

    /**
     * Check if order notifications are enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isOrderNotificationsEnabled(int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLE_ORDER_NOTIFICATIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get order processing template
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrderProcessingTemplate(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_PROCESSING_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
	/**
 * Get phone format
 *
 * @param int|null $storeId
 * @return string
 */
public function getPhoneFormat(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_PHONE_FORMAT,
        ScopeInterface::SCOPE_STORE,
        $storeId
    ) ?: 'local'; // Default format if not set
}

    /**
     * Get order shipped template
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrderShippedTemplate(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_SHIPPED_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get order delivered template
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrderDeliveredTemplate(int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_DELIVERED_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
	/**
 * Check if guest notifications are enabled
 *
 * @param int|null $storeId
 * @return bool
 */
public function isGuestNotificationsEnabled(int $storeId = null): bool
{
    return $this->isEnabled() && 
           $this->isOrderNotificationsEnabled() && 
           (bool)$this->scopeConfig->getValue(
               'magoarab_withoutemail/notifications/enable_guest_notifications',
               ScopeInterface::SCOPE_STORE,
               $storeId
           );
}
/**
 * Get enabled statuses for notification
 *
 * @param int|null $storeId
 * @return array
 */
public function getEnabledStatusesForNotification(int $storeId = null): array
{
    $statuses = $this->scopeConfig->getValue(
        self::XML_PATH_ORDER_STATUSES,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
    
    return $statuses ? explode(',', $statuses) : [];
}

/**
 * Get business name
 *
 * @param int|null $storeId
 * @return string
 */
public function getBusinessName(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_BUSINESS_NAME,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}

/**
 * Get support phone
 *
 * @param int|null $storeId
 * @return string
 */
public function getSupportPhone(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_SUPPORT_PHONE,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}

/**
 * Get notification language
 *
 * @param int|null $storeId
 * @return string
 */
public function getNotificationLanguage(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_NOTIFICATION_LANGUAGE,
        ScopeInterface::SCOPE_STORE,
        $storeId
    ) ?: 'en';
}

/**
 * Get template for specific status
 *
 * @param string $status
 * @param int|null $storeId
 * @return string
 */
public function getTemplateForStatus(string $status, int $storeId = null): string
{
    // Try to get value from config
    $template = $this->scopeConfig->getValue(
        "magoarab_withoutemail/notifications/template_{$status}",
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
    
    // If empty, use default template
    if (empty($template)) {
        $defaultTemplates = [
            'pending' => 'Hello {{customer_name}}, your order #{{order_id}} has been received. Thank you for shopping with {{business_name}}!',
            'processing' => 'Hello {{customer_name}}, your order #{{order_id}} is now being processed. We will notify you once it ships.',
            'complete' => 'Hello {{customer_name}}, your order #{{order_id}} has been completed. Thank you for shopping with {{business_name}}!',
            'canceled' => 'Hello {{customer_name}}, your order #{{order_id}} has been canceled. If you have any questions, please contact us at {{support_phone}}.',
            'holded' => 'Hello {{customer_name}}, your order #{{order_id}} is currently on hold. Our team will contact you soon.',
            'shipped' => 'Hello {{customer_name}}, your order #{{order_id}} has been shipped! Tracking number: {{tracking_number}}',
            'refunded' => 'Hello {{customer_name}}, your refund for order #{{order_id}} has been processed. The amount will be credited to your account within 5-7 business days.'
        ];
        
        $template = $defaultTemplates[$status] ?? "طلبك رقم #{{order_id}} حالته: {$status}";
    }
    
    // Log the template for debugging
    $this->_logger->info("Template for status {$status}: " . $template);
    
    return $template;
}


    /**
 * Check if OTP is enabled
 *
 * @param int|null $storeId
 * @return bool
 */
public function isOtpEnabled(int $storeId = null): bool
{
    return $this->isEnabled() && (bool)$this->scopeConfig->getValue(
        self::XML_PATH_ENABLE_OTP,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}
// Add these methods
public function getTwilioAccountSid(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_TWILIO_ACCOUNT_SID,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}

public function getTwilioAuthToken(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_TWILIO_AUTH_TOKEN,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}

public function getTwilioWhatsappNumber(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_TWILIO_WHATSAPP_NUMBER,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}

public function getWatiApiKey(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_WATI_API_KEY,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}

public function getWatiEndpoint(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        self::XML_PATH_WATI_ENDPOINT,
        ScopeInterface::SCOPE_STORE,
        $storeId
    );
}
    /**
     * Get store domain
     *
     * @param int|null $storeId
     * @return string
     */
    public function getStoreDomain(int $storeId = null): string
    {
        try {
            $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl();
            $parsedUrl = parse_url($baseUrl);
            return $parsedUrl['host'] ?? 'example.com';
        } catch (\Exception $e) {
            return 'example.com';
        }
    }
	/**
 * Get operation mode
 *
 * @param int|null $storeId
 * @return string
 */
public function getOperationMode(int $storeId = null): string
{
    return (string)$this->scopeConfig->getValue(
        'magoarab_withoutemail/general/mode',
        ScopeInterface::SCOPE_STORE,
        $storeId
    ) ?: 'phone_only';
}
/**
 * Get default country
 *
 * @param int|null $storeId
 * @return string
 */


/**
 * Get preferred countries
 *
 * @param int|null $storeId
 * @return array
 */

/**
 * Check if in hybrid mode
 *
 * @param int|null $storeId
 * @return bool
 */
public function isHybridMode(int $storeId = null): bool
{
    return $this->getOperationMode($storeId) === 'hybrid';
}
}