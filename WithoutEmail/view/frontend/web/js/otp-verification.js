/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
define([
    'jquery',
    'mage/url',
    'mage/translate'
], function ($, url, $t) {
    'use strict';
    
    return function (config) {
        $(document).ready(function () {
            var otpLength = config.otpLength || 6;
            var resendDelay = config.resendDelay || 60; // seconds
            
            // Format OTP input
            $('#otp_code').on('input', function () {
                var value = $(this).val();
                // Remove non-digit characters
                value = value.replace(/\D/g, '');
                // Limit to OTP length
                if (value.length > otpLength) {
                    value = value.substring(0, otpLength);
                }
                $(this).val(value);
            });
            
            // Countdown timer for resend button
            function startResendTimer() {
                var $button = $('#send_otp');
                var originalText = $button.text();
                
                $button.prop('disabled', true);
                
                var countdown = resendDelay;
                var interval = setInterval(function () {
                    countdown--;
                    $button.text($t('Resend OTP (%1)').replace('%1', countdown));
                    
                    if (countdown <= 0) {
                        clearInterval(interval);
                        $button.prop('disabled', false);
                        $button.text(originalText);
                    }
                }, 1000);
            }
            
            // Initialize timer when page loads if OTP has been sent
            if ($('#otp_section').is(':visible')) {
                startResendTimer();
            }
            
            // Start timer when OTP is sent
            $(document).on('otp:sent', function () {
                startResendTimer();
            });
        });
    };
});