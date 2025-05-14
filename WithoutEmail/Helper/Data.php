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
use Magento\Framework\App\State;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;

class Data extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_PHONE_MIN_LENGTH = 'magoarab_withoutemail/phone/min_length';
    const XML_PATH_PHONE_MAX_LENGTH = 'magoarab_withoutemail/phone/max_length';
    const XML_PATH_PHONE_FORMAT = 'magoarab_withoutemail/phone/format';
    const XML_PATH_DEFAULT_COUNTRY = 'magoarab_withoutemail/phone/default_country';
    const XML_PATH_PREFERRED_COUNTRIES = 'magoarab_withoutemail/phone/preferred_countries';
    const XML_PATH_ALLOWED_COUNTRIES = 'magoarab_withoutemail/phone/allowed_countries';
    const XML_PATH_HYBRID_MODE = 'magoarab_withoutemail/general/hybrid_mode';
    
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    
    /**
     * @var State
     */
    protected $appState;
    
    /**
     * @var CountryCollectionFactory
     */
    protected $countryCollectionFactory;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param EncryptorInterface $encryptor
     * @param State $appState
     * @param CountryCollectionFactory $countryCollectionFactory
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        State $appState,
        CountryCollectionFactory $countryCollectionFactory
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->appState = $appState;
        $this->countryCollectionFactory = $countryCollectionFactory;
    }
    
    /**
     * Get store base domain
     *
     * @param int|null $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreDomain(int $storeId = null): string
    {
        $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl();
        $parsedUrl = parse_url($baseUrl);
        
        return $parsedUrl['host'] ?? 'example.com';
    }
    
    /**
     * Check if area is frontend
     *
     * @return bool
     */
    public function isFrontend(): bool
    {
        try {
            return $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_FRONTEND;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate a secure random string
     *
     * @param int $length
     * @return string
     */
    public function generateRandomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $randomString;
    }
    
    /**
     * Check if hybrid mode is enabled
     *
     * @return bool
     */
/**
 * Check if hybrid mode is enabled
 *
 * @return bool
 */
		public function isHybridModeEnabled(): bool
		{
			return (bool) $this->scopeConfig->getValue(
				'magoarab_withoutemail/general/hybrid_mode',
				ScopeInterface::SCOPE_STORE
			);
		}

		/**
		 * Check if phone only mode is enabled
		 *
		 * @return bool
		 */
		public function isPhoneOnlyMode(): bool
		{
			return (bool) $this->scopeConfig->getValue(
				'magoarab_withoutemail/general/phone_only_mode',
				ScopeInterface::SCOPE_STORE
			);
		}
    
    /**
     * Get minimum phone length
     *
     * @return int
     */
    public function getMinPhoneLength(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PHONE_MIN_LENGTH,
            ScopeInterface::SCOPE_STORE
        ) ?: 9;
    }
    
    /**
     * Get maximum phone length
     *
     * @return int
     */
    public function getMaxPhoneLength(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PHONE_MAX_LENGTH,
            ScopeInterface::SCOPE_STORE
        ) ?: 15;
    }
    
    /**
     * Get phone format
     *
     * @return string
     */
    public function getPhoneFormat(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PHONE_FORMAT,
            ScopeInterface::SCOPE_STORE
        ) ?: 'international';
    }
    
    /**
     * Get default country
     *
     * @return string
     */
    public function getDefaultCountry(): string
    {
        // Try to get default country from module settings
        $country = $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_COUNTRY,
            ScopeInterface::SCOPE_STORE
        );
        
        // If not set, get default country from Magento general settings
        if (!$country) {
            $country = $this->scopeConfig->getValue(
                'general/country/default',
                ScopeInterface::SCOPE_STORE
            );
        }
        
        return $country ?: 'EG';
    }
    
    /**
     * Get preferred countries
     *
     * @return array
     */
    public function getPreferredCountries(): array
    {
        // Try to get preferred countries from module settings
        $countries = $this->scopeConfig->getValue(
            self::XML_PATH_PREFERRED_COUNTRIES,
            ScopeInterface::SCOPE_STORE
        );
        
        if (!$countries) {
            // If no preferred countries in settings, use default country + popular countries
            $defaultCountry = $this->getDefaultCountry();
            $preferred = [$defaultCountry];
            
            // Add some popular countries if default country is not among them
            $popular = ['US', 'GB', 'CA', 'AU', 'DE', 'FR'];
            foreach ($popular as $country) {
                if ($country !== $defaultCountry) {
                    $preferred[] = $country;
                }
            }
            
            return $preferred;
        }
        
        return explode(',', $countries);
    }
    
    /**
     * Get allowed countries
     *
     * @return array
     */
    public function getAllowedCountries(): array
    {
        // Try to get allowed countries from module settings
        $countries = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_COUNTRIES,
            ScopeInterface::SCOPE_STORE
        );
        
        if (!$countries) {
            // If not set, get allowed countries from Magento general settings
            $countries = $this->scopeConfig->getValue(
                'general/country/allow',
                ScopeInterface::SCOPE_STORE
            );
        }
        
        return $countries ? explode(',', $countries) : [];
    }
    
    /**
     * Get all countries
     *
     * @return array
     */
    public function getAllCountries(): array
    {
        $collection = $this->countryCollectionFactory->create();
        $countries = [];
        
        foreach ($collection as $country) {
            $countries[] = $country->getCountryId();
        }
        
        return $countries;
    }
    
    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logDebug(string $message, array $context = []): void
    {
        $this->_logger->debug($message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logError(string $message, array $context = []): void
    {
        $this->_logger->error($message, $context);
    }
}