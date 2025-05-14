<?php
namespace MagoArab\WithoutEmail\Block\Customer\Account;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use MagoArab\WithoutEmail\Helper\Data as HelperData;

class Forgotpassword extends Template
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @param Context $context
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Context $context,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperData = $helperData;
    }

    /**
     * Get Phone Validator Configuration
     *
     * @return string
     */
    public function getPhoneValidatorConfig()
    {
        $config = [
            'minLength' => $this->helperData->getMinPhoneLength(),
            'maxLength' => $this->helperData->getMaxPhoneLength(),
            'defaultCountry' => $this->helperData->getDefaultCountry(),
            'preferredCountries' => $this->helperData->getPreferredCountries(),
            'allowedCountries' => $this->helperData->getAllowedCountries(),
            'utilsPath' => $this->getViewFileUrl('MagoArab_WithoutEmail/js/intl-tel-input/utils.js')
        ];
        
        return json_encode($config);
    }
    
    /**
     * Check if hybrid mode is enabled
     *
     * @return bool
     */
    public function isHybridModeEnabled()
    {
        return $this->helperData->isHybridModeEnabled();
    }
    
    /**
     * Get minimum password length
     *
     * @return int
     */
    public function getMinPasswordLength()
    {
        return $this->_scopeConfig->getValue(
            \Magento\Customer\Model\AccountManagement::XML_PATH_MINIMUM_PASSWORD_LENGTH
        );
    }
    
    /**
     * Get required character classes number
     *
     * @return int
     */
    public function getRequiredCharacterClassesNumber()
    {
        return $this->_scopeConfig->getValue(
            \Magento\Customer\Model\AccountManagement::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER
        );
    }
    
    /**
     * Get Username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getRequest()->getParam('email');
    }
    
    /**
     * Get login URL
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->getUrl('customer/account/login');
    }
}