<?php
namespace MagoArab\WithoutEmail\Api;

interface SessionInterface
{
    /**
     * Set OTP code
     *
     * @param string $otpCode
     * @return void
     */
    public function setOtpCode($otpCode);

    /**
     * Get OTP code
     *
     * @return string
     */
    public function getOtpCode();

    /**
     * Set OTP phone
     *
     * @param string $phone
     * @return void
     */
    public function setOtpPhone($phone);

    /**
     * Get OTP phone
     *
     * @return string
     */
    public function getOtpPhone();

    /**
     * Set OTP expiry
     *
     * @param int $expiry
     * @return void
     */
    public function setOtpExpiry($expiry);

    /**
     * Get OTP expiry
     *
     * @return int
     */
    public function getOtpExpiry();

    /**
     * Set OTP verified status
     *
     * @param bool $verified
     * @return void
     */
    public function setOtpVerified($verified);

    /**
     * Get OTP verified status
     *
     * @return bool
     */
    public function getOtpVerified();
}