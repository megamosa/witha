define([
    'jquery',
    'mage/url',
    'mage/translate'
], function ($, url, $t) {
    'use strict';

    return function (config, element) {
        var $checkbox = $(element);
        var $otpSection = $('#otp_section');
        var $phoneInput = $('#phone_number');
        var $sendOtpBtn = $('#send_phone_otp');
        var $otpInput = $('#phone_otp');
        var otpSent = false;
        var otpVerified = false;

        $checkbox.on('change', function () {
            if (this.checked) {
                $otpSection.show();
                $phoneInput.prop('readonly', false);
            } else {
                $otpSection.hide();
                $phoneInput.prop('readonly', true);
                $phoneInput.val($phoneInput.data('original-value'));
                otpSent = false;
                otpVerified = false;
            }
        });

        $sendOtpBtn.on('click', function () {
            var phoneNumber = $phoneInput.val();
            
            if (!phoneNumber) {
                alert($t('Please enter a valid phone number'));
                return;
            }

            $.ajax({
                url: url.build('magoarab_withoutemail/otp/send'),
                type: 'POST',
                dataType: 'json',
                data: {
                    phone_number: phoneNumber,
                    type: 'change_phone'
                },
                beforeSend: function () {
                    $sendOtpBtn.prop('disabled', true).text($t('Sending...'));
                },
                success: function (response) {
                    if (response.success) {
                        otpSent = true;
                        alert($t('OTP sent successfully'));
                        $sendOtpBtn.text($t('Resend OTP'));
                    } else {
                        alert(response.message);
                    }
                    $sendOtpBtn.prop('disabled', false);
                },
                error: function () {
                    alert($t('An error occurred. Please try again.'));
                    $sendOtpBtn.prop('disabled', false).text($t('Send OTP'));
                }
            });
        });

        $phoneInput.data('original-value', $phoneInput.val());
        $phoneInput.prop('readonly', true);

        // Validate OTP before form submission
        $('#form-validate').on('submit', function (e) {
            if ($checkbox.is(':checked') && !otpVerified) {
                e.preventDefault();
                
                var otpCode = $otpInput.val();
                if (!otpCode) {
                    alert($t('Please enter OTP code'));
                    return false;
                }

                $.ajax({
                    url: url.build('magoarab_withoutemail/otp/verify'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        phone_number: $phoneInput.val(),
                        otp_code: otpCode,
                        type: 'change_phone'
                    },
                    success: function (response) {
                        if (response.success) {
                            otpVerified = true;
                            $('#form-validate').submit();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function () {
                        alert($t('An error occurred. Please try again.'));
                    }
                });
                
                return false;
            }
        });
    };
});