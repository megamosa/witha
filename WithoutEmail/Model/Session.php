<?php
namespace MagoArab\WithoutEmail\Model;

use Magento\Framework\Session\SessionManager;
use MagoArab\WithoutEmail\Api\SessionInterface;

class Session extends SessionManager implements SessionInterface
{
    /**
     * Set OTP code
     *
     * @param string $otpCode
     * @return void
     */
    public function setOtpCode($otpCode)
    {
        $this->setData('otp_code', $otpCode);
    }

    /**
     * Get OTP code
     *
     * @return string
     */
    public function getOtpCode()
    {
        return $this->getData('otp_code');
    }

    /**
     * Set OTP phone
     *
     * @param string $phone
     * @return void
     */
    public function setOtpPhone($phone)
    {
        $this->setData('otp_phone', $phone);
    }

    /**
     * Get OTP phone
     *
     * @return string
     */
    public function getOtpPhone()
    {
        return $this->getData('otp_phone');
    }

    /**
     * Set OTP expiry
     *
     * @param int $expiry
     * @return void
     */
    public function setOtpExpiry($expiry)
    {
        $this->setData('otp_expiry', $expiry);
    }

    /**
     * Get OTP expiry
     *
     * @return int
     */
    public function getOtpExpiry()
    {
        return $this->getData('otp_expiry');
    }

    /**
     * Set OTP verified status
     *
     * @param bool $verified
     * @return void
     */
    public function setOtpVerified($verified)
    {
        $this->setData('otp_verified', $verified);
    }

    /**
     * Get OTP verified status
     *
     * @return bool
     */
    public function getOtpVerified()
    {
        return $this->getData('otp_verified');
    }
}