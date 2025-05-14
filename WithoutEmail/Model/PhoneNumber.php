<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Model;

use Magento\Framework\DataObject;
use MagoArab\WithoutEmail\Api\Data\PhoneNumberInterface;
use MagoArab\WithoutEmail\Helper\Config as ConfigHelper;

class PhoneNumber extends DataObject implements PhoneNumberInterface
{
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->configHelper = $configHelper;
    }

    /**
     * @inheritDoc
     */
    public function getPhoneNumber(): ?string
    {
        return $this->getData(self::PHONE_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }

    /**
     * @inheritDoc
     */
    public function getOtpCode(): ?string
    {
        return $this->getData(self::OTP_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setOtpCode(string $otpCode): self
    {
        return $this->setData(self::OTP_CODE, $otpCode);
    }

    /**
     * @inheritDoc
     */
    public function getOtpExpiry(): ?string
    {
        return $this->getData(self::OTP_EXPIRY);
    }

    /**
     * @inheritDoc
     */
    public function setOtpExpiry(string $otpExpiry): self
    {
        return $this->setData(self::OTP_EXPIRY, $otpExpiry);
    }

    /**
     * @inheritDoc
     */

public function validatePhoneNumber(): bool
{
    $phoneNumber = $this->getPhoneNumber();
    
    if (empty($phoneNumber)) {
        return false;
    }
    
    $format = $this->configHelper->getPhoneFormat();
    $isValid = true;
    
    switch ($format) {
        case 'local':
            // Only digits allowed
            $isValid = preg_match('/^\d+$/', $phoneNumber);
            break;
        case 'international':
            // Must start with + followed by digits
            $isValid = preg_match('/^\+\d+$/', $phoneNumber);
            break;
        case 'any':
        default:
            // Any format is valid, but still clean non-digits for length validation
            break;
    }
    
    if (!$isValid) {
        return false;
    }
    
    // Clean the phone number for length validation (keep only digits)
    $cleanedNumber = preg_replace('/\D/', '', $phoneNumber);
    
    $minLength = $this->configHelper->getMinPhoneLength();
    $maxLength = $this->configHelper->getMaxPhoneLength();
    
    // Check if phone number length is within allowed range
    if (strlen($cleanedNumber) < $minLength || strlen($cleanedNumber) > $maxLength) {
        return false;
    }
    
    return true;
}

    /**
     * @inheritDoc
     */
    public function validateOtp(string $otpCode): bool
    {
        $storedOtp = $this->getOtpCode();
        $expiryTime = $this->getOtpExpiry();
        
        if (empty($storedOtp) || empty($expiryTime)) {
            return false;
        }
        
        // Check if OTP has expired
        $currentTime = new \DateTime();
        $expiry = new \DateTime($expiryTime);
        
        if ($currentTime > $expiry) {
            return false;
        }
        
        // Check if OTP matches
        return $storedOtp === $otpCode;
    }

    /**
     * Generate email from phone number
     *
     * @param string $phoneNumber
     * @param string $domain
     * @return string
     */
    public function generateEmailFromPhone(string $phoneNumber, string $domain): string
    {
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        
        return $phoneNumber . '@' . $domain;
    }
}