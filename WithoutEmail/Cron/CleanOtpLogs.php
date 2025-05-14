<?php
namespace MagoArab\WithoutEmail\Cron;

use MagoArab\WithoutEmail\Model\OtpRateLimit;

class CleanOtpLogs
{
    protected $otpRateLimit;

    public function __construct(
        OtpRateLimit $otpRateLimit
    ) {
        $this->otpRateLimit = $otpRateLimit;
    }

    public function execute()
    {
        $this->otpRateLimit->cleanOldRecords();
    }
}