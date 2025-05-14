<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Model\Config\Validation;

use Magento\Framework\Validator\AbstractValidator;
use MagoArab\WithoutEmail\Helper\Config;

class PhoneNumber extends AbstractValidator
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param Config $configHelper
     */
    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * Validate phone number
     *
     * @param string $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->_clearMessages();
        
        // Check if value is empty
        if (empty($value)) {
            $this->_addMessages(['Phone number is required.']);
            return false;
        }
        
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/\D/', '', $value);
        
        $minLength = $this->configHelper->getMinPhoneLength();
        $maxLength = $this->configHelper->getMaxPhoneLength();
        
        // Check if phone number length is within allowed range
        if (strlen($phoneNumber) < $minLength) {
            $this->_addMessages(["Phone number must be at least {$minLength} digits."]);
            return false;
        }
        
        if (strlen($phoneNumber) > $maxLength) {
            $this->_addMessages(["Phone number must not exceed {$maxLength} digits."]);
            return false;
        }
        
        return true;
    }
}