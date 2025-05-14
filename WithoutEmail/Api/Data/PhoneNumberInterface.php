<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Api\Data;

interface PhoneNumberInterface
{
    /**
     * Constants for keys of data array
     */
    const PHONE_NUMBER = 'phone_number';
    const OTP_CODE = 'otp_code';
    const OTP_EXPIRY = 'otp_expiry';

    /**
     * Get phone number
     *
     * @return string|null
     */
    public function getPhoneNumber(): ?string;

    /**
     * Set phone number
     *
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber(string $phoneNumber): self;

    /**
     * Get OTP code
     *
     * @return string|null
     */
    public function getOtpCode(): ?string;

    /**
     * Set OTP code
     *
     * @param string $otpCode
     * @return $this
     */
    public function setOtpCode(string $otpCode): self;

    /**
     * Get OTP expiry
     *
     * @return string|null
     */
    public function getOtpExpiry(): ?string;

    /**
     * Set OTP expiry
     *
     * @param string $otpExpiry
     * @return $this
     */
    public function setOtpExpiry(string $otpExpiry): self;

    /**
     * Validate phone number
     *
     * @return bool
     */
    public function validatePhoneNumber(): bool;

    /**
     * Validate OTP code
     *
     * @param string $otpCode
     * @return bool
     */
    public function validateOtp(string $otpCode): bool;
}