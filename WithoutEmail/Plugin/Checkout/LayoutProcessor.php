<?php
namespace MagoArab\WithoutEmail\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor as MagentoLayoutProcessor;
use Magento\Framework\App\ObjectManager;
use MagoArab\WithoutEmail\Helper\Config;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;

class LayoutProcessor
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var CollectionFactory
     */
    protected $countryCollectionFactory;

    /**
     * Constructor
     *
     * @param Config $configHelper
     * @param CollectionFactory $countryCollectionFactory
     */
    public function __construct(
        Config $configHelper,
        CollectionFactory $countryCollectionFactory
    ) {
        $this->configHelper = $configHelper;
        $this->countryCollectionFactory = $countryCollectionFactory;
    }

    /**
     * Process checkout layout
     *
     * @param MagentoLayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        MagentoLayoutProcessor $subject,
        array $jsLayout
    ) {
        if (!$this->configHelper->isEnabled()) {
            return $jsLayout;
        }
        
        // Get services directly from ObjectManager
        $objectManager = ObjectManager::getInstance();
        $customerSession = $objectManager->get(\Magento\Customer\Model\Session::class);
        
        // Path to shipping address fields
        $shippingAddressFieldset = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
        
        // 1. Modify email field
        if (isset($shippingAddressFieldset['email'])) {
            // Hide email field with CSS
            $shippingAddressFieldset['email']['config']['additionalClasses'] = 'hidden-field';
            // Disable visual rules
            $shippingAddressFieldset['email']['config']['visible'] = false;
            // But keep it in the form
            $shippingAddressFieldset['email']['visible'] = false;
        }
        
        // 2. Update telephone field properties
        if (isset($shippingAddressFieldset['telephone'])) {
            // Update phone field properties
            $shippingAddressFieldset['telephone']['sortOrder'] = 10;
            $shippingAddressFieldset['telephone']['label'] = __('Phone Number');
            $shippingAddressFieldset['telephone']['additionalClasses'] = 'phone-field-highlight phone-input';
            $shippingAddressFieldset['telephone']['validation'] = array_merge(
                $shippingAddressFieldset['telephone']['validation'] ?? [],
                [
                    'required-entry' => true,
                    'min_text_length' => $this->configHelper->getMinPhoneLength(),
                    'max_text_length' => $this->configHelper->getMaxPhoneLength()
                ]
            );
            
		// 3. Add JavaScript events to auto-fill email
		$onChangeScript = "
			var phoneNumber = event.target.value;
			if (phoneNumber) {
				// Try to format with international code
				if (phoneNumber.indexOf('+') !== 0 && phoneNumber.indexOf('0') === 0) {
					phoneNumber = '+20' + phoneNumber.substring(1);
				} else if (phoneNumber.indexOf('+') !== 0) {
					phoneNumber = '+20' + phoneNumber;
				}
				
				// Update the field with formatted number
				event.target.value = phoneNumber;
				
				var domain = window.location.hostname;
				var email = phoneNumber.replace(/\\D/g, '') + '@' + domain;
				
				var emailField = document.querySelector('input[name$=\".email\"]');
				if (emailField) {
					emailField.value = email;
					var changeEvent = document.createEvent('HTMLEvents');
					changeEvent.initEvent('change', true, false);
					emailField.dispatchEvent(changeEvent);
				}
			}
			return true;
		";
            
            // Add events to telephone field
            if (!isset($shippingAddressFieldset['telephone']['config'])) {
                $shippingAddressFieldset['telephone']['config'] = [];
            }
            
            // Add custom elementTmpl to add JavaScript events
            $shippingAddressFieldset['telephone']['config']['customScope'] = 'shippingAddress';
            $shippingAddressFieldset['telephone']['config']['template'] = 'ui/form/field';
            $shippingAddressFieldset['telephone']['config']['elementTmpl'] = 'MagoArab_WithoutEmail/form/element/telephone';
            
            // Reorder fields
            $telephoneField = $shippingAddressFieldset['telephone'];
            unset($shippingAddressFieldset['telephone']);
            
            $newFieldset = [
                'telephone' => $telephoneField
            ];
            
            foreach ($shippingAddressFieldset as $fieldName => $fieldConfig) {
                $newFieldset[$fieldName] = $fieldConfig;
            }
            
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'] = $newFieldset;
        }
        
        // 4. Add additional CSS to hide email field
        if (!isset($jsLayout['components']['checkout']['children']['additionalStyles'])) {
            $jsLayout['components']['checkout']['children']['additionalStyles'] = [
                'component' => 'Magento_Ui/js/form/components/html',
                'config' => [
                    'content' => '<style>.hidden-field { display: none !important; }</style>'
                ]
            ];
        }
        
        // 5. Configure telephone field in checkout using intlTelInput
        if (isset($shippingAddressFieldset['telephone'])) {
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone']['config']['elementTmpl'] = 'MagoArab_WithoutEmail/form/element/telephone';
        }

        // 6. Add intlTelInput initialization script for telephone field
        $jsLayout['components']['checkout']['children']['intlTelInit'] = [
            'component' => 'MagoArab_WithoutEmail/js/view/intl-tel-init',
            'config' => [
                'defaultCountry' => $this->configHelper->getDefaultCountry(),
                'preferredCountries' => $this->getPreferredCountries()
            ]
        ];
        
        return $jsLayout;
    }
    
    /**
     * Get preferred countries
     *
     * @return array
     */
    protected function getPreferredCountries()
    {
        // Default preferred countries if none configured
        $preferredCountries = ['EG', 'US', 'GB', 'DE'];
        
        // Try to get from store configuration
        $storePreferred = $this->configHelper->getPreferredCountries();
        if (!empty($storePreferred)) {
            $preferredCountries = $storePreferred;
        }
        
        return $preferredCountries;
    }
}