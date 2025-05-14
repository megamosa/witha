<?php
namespace MagoArab\WithoutEmail\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use MagoArab\WithoutEmail\Helper\Config as ConfigHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;
use MagoArab\WithoutEmail\Model\MessageLogger;

class WhatsappService extends AbstractHelper
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var Json
     */
    protected $json;
    
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * @var MessageLogger
     */
    protected $messageLogger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Curl $curl
     * @param ConfigHelper $configHelper
     * @param Json $json
     * @param EncryptorInterface $encryptor
     * @param LoggerInterface $logger
     * @param MessageLogger $messageLogger
     */
    public function __construct(
        Context $context,
        Curl $curl,
        ConfigHelper $configHelper,
        Json $json,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        MessageLogger $messageLogger
    ) {
        parent::__construct($context);
        $this->curl = $curl;
        $this->configHelper = $configHelper;
        $this->json = $json;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->messageLogger = $messageLogger;
    }

    /**
     * Send OTP via WhatsApp
     *
     * @param string $phoneNumber
     * @param string $otp
     * @param string $messageType
     * @return bool
     * @throws LocalizedException
     */
public function sendOtp(string $phoneNumber, string $otp, string $messageType = 'registration'): bool
{
    if (empty($phoneNumber)) {
        $this->logger->error('Phone number is empty');
        return false;
    }
    
    // Log for debugging
    $this->logger->info('Sending OTP', [
        'phone' => $phoneNumber,
        'otp' => $otp,
        'type' => $messageType
    ]);
    
    $message = $this->getOtpMessageByType($otp, $messageType);
    
    // Make sure the OTP number is in the message
    if (empty($message)) {
        $message = "Your OTP code is: {$otp}";
    }
    
    $provider = $this->configHelper->getWhatsAppProvider();
    
    try {
        switch ($provider) {
                case 'ultramsg':
                    return $this->sendViaUltraMsg($phoneNumber, $message);
                case 'dialog360':
                    return $this->sendViaDialog360($phoneNumber, $message);
                case 'wati':
                    return $this->sendViaWati($phoneNumber, $message);
                case 'twilio':
                    return $this->sendViaTwilio($phoneNumber, $message);
                default:
                    throw new LocalizedException(__('Invalid WhatsApp provider configured.'));
        }
    } catch (\Exception $e) {
        $this->logger->error('WhatsApp Service Error: ' . $e->getMessage());
        return $this->fallbackToNextProvider($provider, $phoneNumber, $message);
    }
}

    /**
     * Send via UltraMsg
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    protected function sendViaUltraMsg(string $phoneNumber, string $message): bool
    {
        $token = $this->configHelper->getUltraMsgApiKey();
        $instance = $this->configHelper->getUltraMsgInstanceId();
        
        if (empty($token) || empty($instance)) {
            $this->logger->error('UltraMsg credentials not configured');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'ultramsg',
                    $message,
                    'failed',
                    'Credentials not configured'
                );
            }
            
            return false;
        }
        
        try {
            $token = $this->encryptor->decrypt($token);
        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt UltraMsg token');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'ultramsg',
                    $message,
                    'failed',
                    'Failed to decrypt token'
                );
            }
            
            return false;
        }
        
        $url = "https://api.ultramsg.com/{$instance}/messages/chat";
        
        // Format phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (substr($cleanPhone, 0, 1) !== '+') {
            $cleanPhone = '+' . $cleanPhone;
        }
        
        $params = [
            'token' => $token,
            'to' => $cleanPhone,
            'body' => $message
        ];
        
        try {
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->curl->setOption(CURLOPT_TIMEOUT, 30);
            $this->curl->post($url, $params);
            
            $response = $this->curl->getBody();
            $this->logger->info('UltraMsg Response: ' . $response);
            
            $result = $this->json->unserialize($response);
            
            if (isset($result['sent']) && $result['sent'] === 'true') {
                if (false) { // Disabled monitoring temporarily
                    $this->messageLogger->logMessage(
                        $phoneNumber,
                        'ultramsg',
                        $message,
                        'success',
                        $response
                    );
                }
                return true;
            }
            
            if (isset($result['error'])) {
                $this->logger->error('UltraMsg Error: ' . $result['error']);
            }
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'ultramsg',
                    $message,
                    'failed',
                    $response
                );
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('UltraMsg Exception: ' . $e->getMessage());
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'ultramsg',
                    $message,
                    'failed',
                    'Exception: ' . $e->getMessage()
                );
            }
            
            return false;
        }
    }

    /**
     * Send via Twilio
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    protected function sendViaTwilio(string $phoneNumber, string $message): bool
    {
        $accountSid = $this->configHelper->getTwilioAccountSid();
        $authToken = $this->configHelper->getTwilioAuthToken();
        $fromNumber = $this->configHelper->getTwilioWhatsappNumber();
        
        if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
            $this->logger->error('Twilio credentials not configured');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'twilio',
                    $message,
                    'failed',
                    'Credentials not configured'
                );
            }
            
            return false;
        }
        
        try {
            $authToken = $this->encryptor->decrypt($authToken);
        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt Twilio auth token');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'twilio',
                    $message,
                    'failed',
                    'Failed to decrypt auth token'
                );
            }
            
            return false;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        
        // Format phone number
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (substr($cleanPhone, 0, 1) !== '+') {
            $cleanPhone = '+' . $cleanPhone;
        }
        
        $params = [
            'From' => 'whatsapp:' . $fromNumber,
            'To' => 'whatsapp:' . $cleanPhone,
            'Body' => $message
        ];
        
        try {
            $this->curl->setCredentials($accountSid, $authToken);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curl->post($url, $params);
            
            $response = $this->curl->getBody();
            $this->logger->info('Twilio Response: ' . $response);
            
            $result = $this->json->unserialize($response);
            
            if (isset($result['sid'])) {
                if (false) { // Disabled monitoring temporarily
                    $this->messageLogger->logMessage(
                        $phoneNumber,
                        'twilio',
                        $message,
                        'success',
                        $response
                    );
                }
                return true;
            }
            
            if (isset($result['message'])) {
                $this->logger->error('Twilio Error: ' . $result['message']);
            }
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'twilio',
                    $message,
                    'failed',
                    $response
                );
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Twilio Exception: ' . $e->getMessage());
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'twilio',
                    $message,
                    'failed',
                    'Exception: ' . $e->getMessage()
                );
            }
            
            return false;
        }
    }

    /**
     * Send via WATI
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    protected function sendViaWati(string $phoneNumber, string $message): bool
    {
        $apiKey = $this->configHelper->getWatiApiKey();
        $endpoint = $this->configHelper->getWatiEndpoint();
        
        if (empty($apiKey) || empty($endpoint)) {
            $this->logger->error('WATI credentials not configured');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'wati',
                    $message,
                    'failed',
                    'Credentials not configured'
                );
            }
            
            return false;
        }
        
        try {
            $apiKey = $this->encryptor->decrypt($apiKey);
        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt WATI API key');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'wati',
                    $message,
                    'failed',
                    'Failed to decrypt API key'
                );
            }
            
            return false;
        }
        
        $url = $endpoint . '/api/v1/sendSessionMessage/' . preg_replace('/[^0-9]/', '', $phoneNumber);
        
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ];
        
        $data = [
            'messageText' => $message
        ];
        
        try {
            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->post($url, $this->json->serialize($data));
            
            $response = $this->curl->getBody();
            $this->logger->info('WATI Response: ' . $response);
            
            $result = $this->json->unserialize($response);
            
            if (isset($result['result']) && $result['result'] === true) {
                if (false) { // Disabled monitoring temporarily
                    $this->messageLogger->logMessage(
                        $phoneNumber,
                        'wati',
                        $message,
                        'success',
                        $response
                    );
                }
                return true;
            }
            
            if (isset($result['message'])) {
                $this->logger->error('WATI Error: ' . $result['message']);
            }
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'wati',
                    $message,
                    'failed',
                    $response
                );
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('WATI Exception: ' . $e->getMessage());
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'wati',
                    $message,
                    'failed',
                    'Exception: ' . $e->getMessage()
                );
            }
            
            return false;
        }
    }

    /**
     * Send via Dialog360
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    protected function sendViaDialog360(string $phoneNumber, string $message): bool
    {
        $apiKey = $this->configHelper->getDialog360ApiKey();
        
        if (empty($apiKey)) {
            $this->logger->error('Dialog360 API key not configured');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'dialog360',
                    $message,
                    'failed',
                    'API key not configured'
                );
            }
            
            return false;
        }
        
        try {
            $apiKey = $this->encryptor->decrypt($apiKey);
        } catch (\Exception $e) {
            $this->logger->error('Failed to decrypt Dialog360 API key');
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'dialog360',
                    $message,
                    'failed',
                    'Failed to decrypt API key'
                );
            }
            
            return false;
        }
        
        $url = "https://waba.360dialog.io/v1/messages";
        
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        $data = [
            'to' => $cleanPhone,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];
        
        $headers = [
            'D360-API-KEY' => $apiKey,
            'Content-Type' => 'application/json'
        ];
        
        try {
            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->post($url, $this->json->serialize($data));
            
            $response = $this->curl->getBody();
            $this->logger->info('Dialog360 Response: ' . $response);
            
            $result = $this->json->unserialize($response);
            
            if (isset($result['messages'][0]['id'])) {
                if (false) { // Disabled monitoring temporarily
                    $this->messageLogger->logMessage(
                        $phoneNumber,
                        'dialog360',
                        $message,
                        'success',
                        $response
                    );
                }
                return true;
            }
            
            if (isset($result['errors'])) {
                $this->logger->error('Dialog360 Error: ' . $this->json->serialize($result['errors']));
            }
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'dialog360',
                    $message,
                    'failed',
                    $response
                );
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Dialog360 Exception: ' . $e->getMessage());
            
            if (false) { // Disabled monitoring temporarily
                $this->messageLogger->logMessage(
                    $phoneNumber,
                    'dialog360',
                    $message,
                    'failed',
                    'Exception: ' . $e->getMessage()
                );
            }
            
            return false;
        }
    }

    /**
     * Fallback to next available provider
     *
     * @param string $currentProvider
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    protected function fallbackToNextProvider(string $currentProvider, string $phoneNumber, string $message): bool
    {
        $providers = ['ultramsg', 'dialog360', 'wati', 'twilio'];
        $currentIndex = array_search($currentProvider, $providers);
        
        if ($currentIndex !== false && $currentIndex < count($providers) - 1) {
            $nextProvider = $providers[$currentIndex + 1];
            $this->logger->info("Falling back from {$currentProvider} to {$nextProvider}");
            
            switch ($nextProvider) {
                case 'ultramsg':
                    return $this->sendViaUltraMsg($phoneNumber, $message);
                case 'dialog360':
                    return $this->sendViaDialog360($phoneNumber, $message);
                case 'wati':
                    return $this->sendViaWati($phoneNumber, $message);
                case 'twilio':
                    return $this->sendViaTwilio($phoneNumber, $message);
            }
        }
        
        return false;
    }

    /**
     * Generate OTP code
     *
     * @return string
     */
    public function generateOtp(): string
    {
        $otpLength = $this->configHelper->getOtpLength();
        $characters = '0123456789';
        $otp = '';
        
        for ($i = 0; $i < $otpLength; $i++) {
            $otp .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $otp;
    }

    /**
     * Get OTP message by type
     *
     * @param string $otp
     * @param string $messageType
     * @return string
     */
    protected function getOtpMessageByType(string $otp, string $messageType): string
    {
        switch ($messageType) {
            case 'registration':
                return __('Your registration OTP code is: %1. This code will expire in %2 minutes.', 
                    $otp, 
                    $this->configHelper->getOtpExpiry()
                );
            case 'forgot_password':
                return __('Your password reset OTP code is: %1. This code will expire in %2 minutes.', 
                    $otp, 
                    $this->configHelper->getOtpExpiry()
                );
            case 'change_phone':
                return __('Your phone number change OTP code is: %1. This code will expire in %2 minutes.', 
                    $otp, 
                    $this->configHelper->getOtpExpiry()
                );
            default:
                return __('Your OTP code is: %1. This code will expire in %2 minutes.', 
                    $otp, 
                    $this->configHelper->getOtpExpiry()
                );
        }
    }

    /**
     * Send order status notification
     *
     * @param string $phoneNumber
     * @param array $params
     * @param string $status
     * @return bool
     */
public function sendOrderStatusNotification(string $phoneNumber, array $params, string $status): bool
{
    $message = $this->getOrderStatusMessage($params, $status);
    $provider = $this->configHelper->getWhatsAppProvider();
    
    try {
        switch ($provider) {
            case 'ultramsg':
                return $this->sendViaUltraMsg($phoneNumber, $message);
            case 'dialog360':
                return $this->sendViaDialog360($phoneNumber, $message);
            case 'wati':
                return $this->sendViaWati($phoneNumber, $message);
            case 'twilio':
                return $this->sendViaTwilio($phoneNumber, $message);
            default:
                throw new LocalizedException(__('Invalid WhatsApp provider configured.'));
        }
    } catch (\Exception $e) {
        $this->logger->error('WhatsApp Service Error: ' . $e->getMessage());
        return $this->fallbackToNextProvider($provider, $phoneNumber, $message);
    }
}

    /**
     * Get order status message
     *
     * @param array $params
     * @param string $status
     * @return string
     */
 protected function getOrderStatusMessage(array $params, string $status): string
{
    // Use templates from config
    $template = $this->configHelper->getTemplateForStatus($status);
    
    if (empty($template)) {
        $template = $this->getDefaultTemplate($status);
    }
    
    // Replace placeholders
    $placeholders = [
        '{{order_id}}' => $params['order_id'] ?? '',
        '{{customer_name}}' => $params['customer_name'] ?? '',
        '{{order_total}}' => $params['order_total'] ?? '',
        '{{tracking_number}}' => $params['tracking_number'] ?? '',
        '{{business_name}}' => $this->configHelper->getBusinessName(),
        '{{support_phone}}' => $this->configHelper->getSupportPhone(),
        '{{order_date}}' => $params['order_date'] ?? '',
        '{{delivery_date}}' => $params['delivery_date'] ?? '',
        '{{payment_method}}' => $params['payment_method'] ?? '',
        '{{shipping_method}}' => $params['shipping_method'] ?? '',
        '{{order_status}}' => ucfirst($status),
        '{{order_link}}' => $params['order_link'] ?? ''
    ];
    
    $message = $template;
    foreach ($placeholders as $placeholder => $value) {
        $message = str_replace($placeholder, $value, $message);
    }
    
    return $message;
}
/**
 * Format and validate phone number
 *
 * @param string $phoneNumber
 * @return string|false
 */
public function formatPhoneNumber(string $phoneNumber)
{
    // Remove any non-digit characters except + at the beginning
    $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
    
    // If already has international format, just clean it
    if (substr($cleaned, 0, 1) === '+') {
        return '+' . preg_replace('/\D/', '', substr($cleaned, 1));
    }
    
    // Check if it meets minimum length requirements
    $minLength = $this->configHelper->getMinPhoneLength();
    $maxLength = $this->configHelper->getMaxPhoneLength();
    
    // Count digits only for length check
    $digitsOnly = preg_replace('/\D/', '', $cleaned);
    if (strlen($digitsOnly) < $minLength || strlen($digitsOnly) > $maxLength) {
        $this->_logger->warning('Invalid phone number length: ' . $cleaned);
        return false;
    }
    
    // Get default country code from store config
    $defaultCountry = $this->configHelper->getDefaultCountry();
    $countryCodes = [
        'AF' => '93',   // Afghanistan
        'AL' => '355',  // Albania
        'DZ' => '213',  // Algeria
        'AS' => '1684', // American Samoa
        'AD' => '376',  // Andorra
        'AO' => '244',  // Angola
        'AI' => '1264', // Anguilla
        'AG' => '1268', // Antigua and Barbuda
        'AR' => '54',   // Argentina
        'AM' => '374',  // Armenia
        'AW' => '297',  // Aruba
        'AU' => '61',   // Australia
        'AT' => '43',   // Austria
        'AZ' => '994',  // Azerbaijan
        'BS' => '1242', // Bahamas
        'BH' => '973',  // Bahrain
        'BD' => '880',  // Bangladesh
        'BB' => '1246', // Barbados
        'BY' => '375',  // Belarus
        'BE' => '32',   // Belgium
        'BZ' => '501',  // Belize
        'BJ' => '229',  // Benin
        'BM' => '1441', // Bermuda
        'BT' => '975',  // Bhutan
        'BO' => '591',  // Bolivia
        'BA' => '387',  // Bosnia and Herzegovina
        'BW' => '267',  // Botswana
        'BR' => '55',   // Brazil
        'IO' => '246',  // British Indian Ocean Territory
        'VG' => '1284', // British Virgin Islands
        'BN' => '673',  // Brunei
        'BG' => '359',  // Bulgaria
        'BF' => '226',  // Burkina Faso
        'BI' => '257',  // Burundi
        'KH' => '855',  // Cambodia
        'CM' => '237',  // Cameroon
        'CA' => '1',    // Canada
        'CV' => '238',  // Cape Verde
        'KY' => '1345', // Cayman Islands
        'CF' => '236',  // Central African Republic
        'TD' => '235',  // Chad
        'CL' => '56',   // Chile
        'CN' => '86',   // China
        'CX' => '61',   // Christmas Island
        'CC' => '61',   // Cocos Islands
        'CO' => '57',   // Colombia
        'KM' => '269',  // Comoros
        'CK' => '682',  // Cook Islands
        'CR' => '506',  // Costa Rica
        'HR' => '385',  // Croatia
        'CU' => '53',   // Cuba
        'CW' => '599',  // Curaçao
        'CY' => '357',  // Cyprus
        'CZ' => '420',  // Czech Republic
        'CD' => '243',  // Democratic Republic of the Congo
        'DK' => '45',   // Denmark
        'DJ' => '253',  // Djibouti
        'DM' => '1767', // Dominica
        'DO' => '1809', // Dominican Republic
        'TL' => '670',  // East Timor
        'EC' => '593',  // Ecuador
        'EG' => '20',   // Egypt
        'SV' => '503',  // El Salvador
        'GQ' => '240',  // Equatorial Guinea
        'ER' => '291',  // Eritrea
        'EE' => '372',  // Estonia
        'ET' => '251',  // Ethiopia
        'FK' => '500',  // Falkland Islands
        'FO' => '298',  // Faroe Islands
        'FJ' => '679',  // Fiji
        'FI' => '358',  // Finland
        'FR' => '33',   // France
        'PF' => '689',  // French Polynesia
        'GA' => '241',  // Gabon
        'GM' => '220',  // Gambia
        'GE' => '995',  // Georgia
        'DE' => '49',   // Germany
        'GH' => '233',  // Ghana
        'GI' => '350',  // Gibraltar
        'GR' => '30',   // Greece
        'GL' => '299',  // Greenland
        'GD' => '1473', // Grenada
        'GU' => '1671', // Guam
        'GT' => '502',  // Guatemala
        'GG' => '44',   // Guernsey
        'GN' => '224',  // Guinea
        'GW' => '245',  // Guinea-Bissau
        'GY' => '592',  // Guyana
        'HT' => '509',  // Haiti
        'HN' => '504',  // Honduras
        'HK' => '852',  // Hong Kong
        'HU' => '36',   // Hungary
        'IS' => '354',  // Iceland
        'IN' => '91',   // India
        'ID' => '62',   // Indonesia
        'IR' => '98',   // Iran
        'IQ' => '964',  // Iraq
        'IE' => '353',  // Ireland
        'IM' => '44',   // Isle of Man
        'IL' => '972',  // Israel
        'IT' => '39',   // Italy
        'CI' => '225',  // Ivory Coast
        'JM' => '1876', // Jamaica
        'JP' => '81',   // Japan
        'JE' => '44',   // Jersey
        'JO' => '962',  // Jordan
        'KZ' => '7',    // Kazakhstan
        'KE' => '254',  // Kenya
        'KI' => '686',  // Kiribati
        'KW' => '965',  // Kuwait
        'KG' => '996',  // Kyrgyzstan
        'LA' => '856',  // Laos
        'LV' => '371',  // Latvia
        'LB' => '961',  // Lebanon
        'LS' => '266',  // Lesotho
        'LR' => '231',  // Liberia
        'LY' => '218',  // Libya
        'LI' => '423',  // Liechtenstein
        'LT' => '370',  // Lithuania
        'LU' => '352',  // Luxembourg
        'MO' => '853',  // Macau
        'MK' => '389',  // Macedonia
        'MG' => '261',  // Madagascar
        'MW' => '265',  // Malawi
        'MY' => '60',   // Malaysia
        'MV' => '960',  // Maldives
        'ML' => '223',  // Mali
        'MT' => '356',  // Malta
        'MH' => '692',  // Marshall Islands
        'MR' => '222',  // Mauritania
        'MU' => '230',  // Mauritius
        'YT' => '262',  // Mayotte
        'MX' => '52',   // Mexico
        'FM' => '691',  // Micronesia
        'MD' => '373',  // Moldova
        'MC' => '377',  // Monaco
        'MN' => '976',  // Mongolia
        'ME' => '382',  // Montenegro
        'MS' => '1664', // Montserrat
        'MA' => '212',  // Morocco
        'MZ' => '258',  // Mozambique
        'MM' => '95',   // Myanmar
        'NA' => '264',  // Namibia
        'NR' => '674',  // Nauru
        'NP' => '977',  // Nepal
        'NL' => '31',   // Netherlands
        'AN' => '599',  // Netherlands Antilles
        'NC' => '687',  // New Caledonia
        'NZ' => '64',   // New Zealand
        'NI' => '505',  // Nicaragua
        'NE' => '227',  // Niger
        'NG' => '234',  // Nigeria
        'NU' => '683',  // Niue
        'KP' => '850',  // North Korea
        'MP' => '1670', // Northern Mariana Islands
        'NO' => '47',   // Norway
        'OM' => '968',  // Oman
        'PK' => '92',   // Pakistan
        'PW' => '680',  // Palau
        'PS' => '970',  // Palestine
        'PA' => '507',  // Panama
        'PG' => '675',  // Papua New Guinea
        'PY' => '595',  // Paraguay
        'PE' => '51',   // Peru
        'PH' => '63',   // Philippines
        'PN' => '64',   // Pitcairn
        'PL' => '48',   // Poland
        'PT' => '351',  // Portugal
        'PR' => '1787', // Puerto Rico
        'QA' => '974',  // Qatar
        'CG' => '242',  // Republic of the Congo
        'RE' => '262',  // Reunion
        'RO' => '40',   // Romania
        'RU' => '7',    // Russia
        'RW' => '250',  // Rwanda
        'BL' => '590',  // Saint Barthelemy
        'SH' => '290',  // Saint Helena
        'KN' => '1869', // Saint Kitts and Nevis
        'LC' => '1758', // Saint Lucia
        'MF' => '590',  // Saint Martin
        'PM' => '508',  // Saint Pierre and Miquelon
        'VC' => '1784', // Saint Vincent and the Grenadines
        'WS' => '685',  // Samoa
        'SM' => '378',  // San Marino
        'ST' => '239',  // Sao Tome and Principe
        'SA' => '966',  // Saudi Arabia
        'SN' => '221',  // Senegal
        'RS' => '381',  // Serbia
        'SC' => '248',  // Seychelles
        'SL' => '232',  // Sierra Leone
        'SG' => '65',   // Singapore
        'SX' => '1721', // Sint Maarten
        'SK' => '421',  // Slovakia
        'SI' => '386',  // Slovenia
        'SB' => '677',  // Solomon Islands
        'SO' => '252',  // Somalia
        'ZA' => '27',   // South Africa
        'KR' => '82',   // South Korea
        'SS' => '211',  // South Sudan
        'ES' => '34',   // Spain
        'LK' => '94',   // Sri Lanka
        'SD' => '249',  // Sudan
        'SR' => '597',  // Suriname
        'SJ' => '47',   // Svalbard and Jan Mayen
        'SZ' => '268',  // Swaziland
        'SE' => '46',   // Sweden
        'CH' => '41',   // Switzerland
        'SY' => '963',  // Syria
        'TW' => '886',  // Taiwan
        'TJ' => '992',  // Tajikistan
        'TZ' => '255',  // Tanzania
        'TH' => '66',   // Thailand
        'TG' => '228',  // Togo
        'TK' => '690',  // Tokelau
        'TO' => '676',  // Tonga
        'TT' => '1868', // Trinidad and Tobago
        'TN' => '216',  // Tunisia
        'TR' => '90',   // Turkey
        'TM' => '993',  // Turkmenistan
        'TC' => '1649', // Turks and Caicos Islands
        'TV' => '688',  // Tuvalu
        'VI' => '1340', // U.S. Virgin Islands
        'UG' => '256',  // Uganda
        'UA' => '380',  // Ukraine
        'AE' => '971',  // United Arab Emirates
        'GB' => '44',   // United Kingdom
        'US' => '1',    // United States
        'UY' => '598',  // Uruguay
        'UZ' => '998',  // Uzbekistan
        'VU' => '678',  // Vanuatu
        'VA' => '379',  // Vatican
        'VE' => '58',   // Venezuela
        'VN' => '84',   // Vietnam
        'WF' => '681',  // Wallis and Futuna
        'EH' => '212',  // Western Sahara
        'YE' => '967',  // Yemen
        'ZM' => '260',  // Zambia
        'ZW' => '263',  // Zimbabwe
    ];
    
    $defaultCountryCode = $countryCodes[$defaultCountry] ?? '20'; // Default to Egypt if not found
    
    // Format the number with country code
    if (substr($cleaned, 0, 1) === '0') {
        // Remove leading 0 and add country code
        $cleaned = '+' . $defaultCountryCode . substr($cleaned, 1);
    } else {
        // Just add country code if not starting with 0
        $cleaned = '+' . $defaultCountryCode . $cleaned;
    }
    
    return $cleaned;
}

/**
 * Get country dial code from country code
 *
 * @param string $countryCode
 * @return string
 */
public function getCountryDialCode(string $countryCode): string
{
    $countryCodes = [
        'AF' => '93',   // Afghanistan
        'AL' => '355',  // Albania
        'DZ' => '213',  // Algeria
        'AS' => '1684', // American Samoa
        'AD' => '376',  // Andorra
        'AO' => '244',  // Angola
        'AI' => '1264', // Anguilla
        'AG' => '1268', // Antigua and Barbuda
        'AR' => '54',   // Argentina
        'AM' => '374',  // Armenia
        'AW' => '297',  // Aruba
        'AU' => '61',   // Australia
        'AT' => '43',   // Austria
        'AZ' => '994',  // Azerbaijan
        'BS' => '1242', // Bahamas
        'BH' => '973',  // Bahrain
        'BD' => '880',  // Bangladesh
        'BB' => '1246', // Barbados
        'BY' => '375',  // Belarus
        'BE' => '32',   // Belgium
        'BZ' => '501',  // Belize
        'BJ' => '229',  // Benin
        'BM' => '1441', // Bermuda
        'BT' => '975',  // Bhutan
        'BO' => '591',  // Bolivia
        'BA' => '387',  // Bosnia and Herzegovina
        'BW' => '267',  // Botswana
        'BR' => '55',   // Brazil
        'IO' => '246',  // British Indian Ocean Territory
        'VG' => '1284', // British Virgin Islands
        'BN' => '673',  // Brunei
        'BG' => '359',  // Bulgaria
        'BF' => '226',  // Burkina Faso
        'BI' => '257',  // Burundi
        'KH' => '855',  // Cambodia
        'CM' => '237',  // Cameroon
        'CA' => '1',    // Canada
        'CV' => '238',  // Cape Verde
        'KY' => '1345', // Cayman Islands
        'CF' => '236',  // Central African Republic
        'TD' => '235',  // Chad
        'CL' => '56',   // Chile
        'CN' => '86',   // China
        'CX' => '61',   // Christmas Island
        'CC' => '61',   // Cocos Islands
        'CO' => '57',   // Colombia
        'KM' => '269',  // Comoros
        'CK' => '682',  // Cook Islands
        'CR' => '506',  // Costa Rica
        'HR' => '385',  // Croatia
        'CU' => '53',   // Cuba
        'CW' => '599',  // Curaçao
        'CY' => '357',  // Cyprus
        'CZ' => '420',  // Czech Republic
        'CD' => '243',  // Democratic Republic of the Congo
        'DK' => '45',   // Denmark
        'DJ' => '253',  // Djibouti
        'DM' => '1767', // Dominica
        'DO' => '1809', // Dominican Republic
        'TL' => '670',  // East Timor
        'EC' => '593',  // Ecuador
        'EG' => '20',   // Egypt
        'SV' => '503',  // El Salvador
        'GQ' => '240',  // Equatorial Guinea
        'ER' => '291',  // Eritrea
        'EE' => '372',  // Estonia
        'ET' => '251',  // Ethiopia
        'FK' => '500',  // Falkland Islands
        'FO' => '298',  // Faroe Islands
        'FJ' => '679',  // Fiji
        'FI' => '358',  // Finland
        'FR' => '33',   // France
        'PF' => '689',  // French Polynesia
        'GA' => '241',  // Gabon
        'GM' => '220',  // Gambia
        'GE' => '995',  // Georgia
        'DE' => '49',   // Germany
        'GH' => '233',  // Ghana
        'GI' => '350',  // Gibraltar
        'GR' => '30',   // Greece
        'GL' => '299',  // Greenland
        'GD' => '1473', // Grenada
        'GU' => '1671', // Guam
        'GT' => '502',  // Guatemala
        'GG' => '44',   // Guernsey
        'GN' => '224',  // Guinea
        'GW' => '245',  // Guinea-Bissau
        'GY' => '592',  // Guyana
        'HT' => '509',  // Haiti
        'HN' => '504',  // Honduras
        'HK' => '852',  // Hong Kong
        'HU' => '36',   // Hungary
        'IS' => '354',  // Iceland
        'IN' => '91',   // India
        'ID' => '62',   // Indonesia
        'IR' => '98',   // Iran
        'IQ' => '964',  // Iraq
        'IE' => '353',  // Ireland
        'IM' => '44',   // Isle of Man
        'IL' => '972',  // Israel
        'IT' => '39',   // Italy
        'CI' => '225',  // Ivory Coast
        'JM' => '1876', // Jamaica
        'JP' => '81',   // Japan
        'JE' => '44',   // Jersey
        'JO' => '962',  // Jordan
        'KZ' => '7',    // Kazakhstan
        'KE' => '254',  // Kenya
        'KI' => '686',  // Kiribati
        'KW' => '965',  // Kuwait
        'KG' => '996',  // Kyrgyzstan
        'LA' => '856',  // Laos
        'LV' => '371',  // Latvia
        'LB' => '961',  // Lebanon
        'LS' => '266',  // Lesotho
        'LR' => '231',  // Liberia
        'LY' => '218',  // Libya
        'LI' => '423',  // Liechtenstein
        'LT' => '370',  // Lithuania
        'LU' => '352',  // Luxembourg
        'MO' => '853',  // Macau
        'MK' => '389',  // Macedonia
        'MG' => '261',  // Madagascar
        'MW' => '265',  // Malawi
        'MY' => '60',   // Malaysia
        'MV' => '960',  // Maldives
        'ML' => '223',  // Mali
        'MT' => '356',  // Malta
        'MH' => '692',  // Marshall Islands
        'MR' => '222',  // Mauritania
        'MU' => '230',  // Mauritius
        'YT' => '262',  // Mayotte
        'MX' => '52',   // Mexico
        'FM' => '691',  // Micronesia
        'MD' => '373',  // Moldova
        'MC' => '377',  // Monaco
        'MN' => '976',  // Mongolia
        'ME' => '382',  // Montenegro
        'MS' => '1664', // Montserrat
        'MA' => '212',  // Morocco
        'MZ' => '258',  // Mozambique
        'MM' => '95',   // Myanmar
        'NA' => '264',  // Namibia
        'NR' => '674',  // Nauru
        'NP' => '977',  // Nepal
        'NL' => '31',   // Netherlands
        'AN' => '599',  // Netherlands Antilles
        'NC' => '687',  // New Caledonia
        'NZ' => '64',   // New Zealand
        'NI' => '505',  // Nicaragua
        'NE' => '227',  // Niger
        'NG' => '234',  // Nigeria
        'NU' => '683',  // Niue
        'KP' => '850',  // North Korea
        'MP' => '1670', // Northern Mariana Islands
        'NO' => '47',   // Norway
        'OM' => '968',  // Oman
        'PK' => '92',   // Pakistan
        'PW' => '680',  // Palau
        'PS' => '970',  // Palestine
        'PA' => '507',  // Panama
        'PG' => '675',  // Papua New Guinea
        'PY' => '595',  // Paraguay
        'PE' => '51',   // Peru
        'PH' => '63',   // Philippines
        'PN' => '64',   // Pitcairn
        'PL' => '48',   // Poland
        'PT' => '351',  // Portugal
        'PR' => '1787', // Puerto Rico
        'QA' => '974',  // Qatar
        'CG' => '242',  // Republic of the Congo
        'RE' => '262',  // Reunion
        'RO' => '40',   // Romania
        'RU' => '7',    // Russia
        'RW' => '250',  // Rwanda
        'BL' => '590',  // Saint Barthelemy
        'SH' => '290',  // Saint Helena
        'KN' => '1869', // Saint Kitts and Nevis
        'LC' => '1758', // Saint Lucia
        'MF' => '590',  // Saint Martin
        'PM' => '508',  // Saint Pierre and Miquelon
        'VC' => '1784', // Saint Vincent and the Grenadines
        'WS' => '685',  // Samoa
        'SM' => '378',  // San Marino
        'ST' => '239',  // Sao Tome and Principe
        'SA' => '966',  // Saudi Arabia
        'SN' => '221',  // Senegal
        'RS' => '381',  // Serbia
        'SC' => '248',  // Seychelles
        'SL' => '232',  // Sierra Leone
        'SG' => '65',   // Singapore
        'SX' => '1721', // Sint Maarten
        'SK' => '421',  // Slovakia
        'SI' => '386',  // Slovenia
        'SB' => '677',  // Solomon Islands
        'SO' => '252',  // Somalia
        'ZA' => '27',   // South Africa
        'KR' => '82',   // South Korea
        'SS' => '211',  // South Sudan
        'ES' => '34',   // Spain
        'LK' => '94',   // Sri Lanka
        'SD' => '249',  // Sudan
        'SR' => '597',  // Suriname
        'SJ' => '47',   // Svalbard and Jan Mayen
        'SZ' => '268',  // Swaziland
        'SE' => '46',   // Sweden
        'CH' => '41',   // Switzerland
        'SY' => '963',  // Syria
        'TW' => '886',  // Taiwan
        'TJ' => '992',  // Tajikistan
        'TZ' => '255',  // Tanzania
        'TH' => '66',   // Thailand
        'TG' => '228',  // Togo
        'TK' => '690',  // Tokelau
        'TO' => '676',  // Tonga
        'TT' => '1868', // Trinidad and Tobago
        'TN' => '216',  // Tunisia
        'TR' => '90',   // Turkey
        'TM' => '993',  // Turkmenistan
        'TC' => '1649', // Turks and Caicos Islands
        'TV' => '688',  // Tuvalu
        'VI' => '1340', // U.S. Virgin Islands
        'UG' => '256',  // Uganda
        'UA' => '380',  // Ukraine
        'AE' => '971',  // United Arab Emirates
        'GB' => '44',   // United Kingdom
        'US' => '1',    // United States
        'UY' => '598',  // Uruguay
        'UZ' => '998',  // Uzbekistan
        'VU' => '678',  // Vanuatu
        'VA' => '379',  // Vatican
        'VE' => '58',   // Venezuela
        'VN' => '84',   // Vietnam
        'WF' => '681',  // Wallis and Futuna
        'EH' => '212',  // Western Sahara
        'YE' => '967',  // Yemen
        'ZM' => '260',  // Zambia
        'ZW' => '263',  // Zimbabwe
    ];
    
    return $countryCodes[strtoupper($countryCode)] ?? '20'; // Default to Egypt if not found
}
    /**
     * Get default template
     *
     * @param string $status
     * @return string
     */
    protected function getDefaultTemplate(string $status): string
    {
        $templates = [
            'pending' => 'Hello {{customer_name}}, your order #{{order_id}} has been received.',
            'processing' => 'Hello {{customer_name}}, your order #{{order_id}} is being processed.',
            'complete' => 'Hello {{customer_name}}, your order #{{order_id}} has been completed.',
            'canceled' => 'Hello {{customer_name}}, your order #{{order_id}} has been canceled.',
            'holded' => 'Hello {{customer_name}}, your order #{{order_id}} is on hold.',
            'shipped' => 'Hello {{customer_name}}, your order #{{order_id}} has been shipped!',
            'refunded' => 'Hello {{customer_name}}, your order #{{order_id}} has been refunded.'
        ];
        
        return $templates[$status] ?? 'Order #{{order_id}} status: {{order_status}}';
    }
}