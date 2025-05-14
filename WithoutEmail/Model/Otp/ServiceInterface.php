<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Model\Otp;

interface ServiceInterface
{
    /**
     * Send OTP message via WhatsApp
     *
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function sendMessage(string $phoneNumber, string $message): bool;
    
    /**
     * Get service provider name
     *
     * @return string
     */
    public function getProviderName(): string;
    
    /**
     * Check if service is configured
     *
     * @return bool
     */
    public function isConfigured(): bool;
}