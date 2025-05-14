<?php
namespace MagoArab\WithoutEmail\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class OtpRateLimit
{
    const TABLE_NAME = 'magoarab_otp_rate_limit';
    const MAX_ATTEMPTS_PER_HOUR = 5;
    const MAX_ATTEMPTS_PER_DAY = 20;
    const BLOCK_DURATION = 3600; // 1 hour in seconds
    
    protected $resource;
    protected $connection;
    protected $dateTime;
    protected $remoteAddress;

    public function __construct(
        ResourceConnection $resource,
        DateTime $dateTime,
        RemoteAddress $remoteAddress
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->dateTime = $dateTime;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * Check if the user can send OTP
     */
    public function canSendOtp($phoneNumber)
    {
        $ip = $this->remoteAddress->getRemoteAddress();
        
        // Check if IP is blocked
        if ($this->isIpBlocked($ip)) {
            return [
                'allowed' => false,
                'message' => __('Too many attempts. Please try again after 1 hour.')
            ];
        }
        
        // Check rate limits
        $attempts = $this->getAttempts($phoneNumber, $ip);
        
        if ($attempts['hour'] >= self::MAX_ATTEMPTS_PER_HOUR) {
            return [
                'allowed' => false,
                'message' => __('Too many requests. Please wait for an hour before trying again.')
            ];
        }
        
        if ($attempts['day'] >= self::MAX_ATTEMPTS_PER_DAY) {
            return [
                'allowed' => false,
                'message' => __('Daily limit reached. Please try again tomorrow.')
            ];
        }
        
        // Check cooldown
        $lastAttempt = $this->getLastAttemptTime($phoneNumber);
        if ($lastAttempt) {
            $timeDiff = time() - strtotime($lastAttempt);
            if ($timeDiff < 60) { // 60 seconds cooldown
                return [
                    'allowed' => false,
                    'message' => __('Please wait %1 seconds before requesting another OTP.', 60 - $timeDiff)
                ];
            }
        }
        
        return ['allowed' => true];
    }

    /**
     * Log OTP attempt
     */
    public function logAttempt($phoneNumber, $success = true)
    {
        $ip = $this->remoteAddress->getRemoteAddress();
        $table = $this->resource->getTableName(self::TABLE_NAME);
        
        $this->connection->insert($table, [
            'phone_number' => $phoneNumber,
            'ip_address' => $ip,
            'attempt_time' => $this->dateTime->gmtDate(),
            'success' => $success ? 1 : 0
        ]);
    }

    /**
     * Get attempts count
     */
    protected function getAttempts($phoneNumber, $ip)
    {
        $table = $this->resource->getTableName(self::TABLE_NAME);
        
        // Last hour
        $hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $hourCount = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} 
             WHERE (phone_number = ? OR ip_address = ?) 
             AND attempt_time > ?",
            [$phoneNumber, $ip, $hourAgo]
        );
        
        // Last day
        $dayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));
        $dayCount = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} 
             WHERE (phone_number = ? OR ip_address = ?) 
             AND attempt_time > ?",
            [$phoneNumber, $ip, $dayAgo]
        );
        
        return [
            'hour' => (int) $hourCount,
            'day' => (int) $dayCount
        ];
    }

    /**
     * Check if IP is blocked
     */
    protected function isIpBlocked($ip)
    {
        $table = $this->resource->getTableName(self::TABLE_NAME);
        $hourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $failedAttempts = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} 
             WHERE ip_address = ? 
             AND attempt_time > ? 
             AND success = 0",
            [$ip, $hourAgo]
        );
        
        return $failedAttempts > 10; // Block after 10 failed attempts in an hour
    }

    /**
     * Get last attempt time
     */
    protected function getLastAttemptTime($phoneNumber)
    {
        $table = $this->resource->getTableName(self::TABLE_NAME);
        
        return $this->connection->fetchOne(
            "SELECT attempt_time FROM {$table} 
             WHERE phone_number = ? 
             ORDER BY attempt_time DESC 
             LIMIT 1",
            [$phoneNumber]
        );
    }

    /**
     * Clean old records
     */
    public function cleanOldRecords()
    {
        $table = $this->resource->getTableName(self::TABLE_NAME);
        $weekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));
        
        $this->connection->delete($table, ['attempt_time <= ?' => $weekAgo]);
    }
}