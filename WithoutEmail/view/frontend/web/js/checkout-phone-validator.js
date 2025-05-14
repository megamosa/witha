define([
    'jquery',
    'mage/utils/wrapper',
    'intlTelInput'
], function ($, wrapper) {
    'use strict';
    
    return function() {
        // Run initialization after page is loaded
        $(document).ready(function() {
            // Function to watch for telephone field
            function initializePhoneField() {
                var telephoneField = $('#telephone');
                
                if (telephoneField.length && typeof window.intlTelInput === 'function' && !telephoneField.data('iti')) {
                    try {
                        var iti = window.intlTelInput(telephoneField[0], {
                            initialCountry: 'EG',
                            preferredCountries: ['EG', 'US', 'GB', 'SA', 'AE'],
                            separateDialCode: true,
                            nationalMode: true,
                            formatOnDisplay: false,
                            utilsScript: require.toUrl('MagoArab_WithoutEmail/js/utils.js')
                        });
                        
                        telephoneField.data('iti', iti);
                        
                        // Format on blur
                        telephoneField.on('blur', function() {
                            formatPhoneNumber(telephoneField[0], iti);
                        });
                        
                        // Override Magento validation
                        var originalValidate = telephoneField.validation('isValid');
                        telephoneField.validation = wrapper.wrap(telephoneField.validation, function(original) {
                            var result = original();
                            if (result === false) {
                                // Still try to format the number
                                formatPhoneNumber(telephoneField[0], iti);
                                // Assume valid and continue
                                return true;
                            }
                            return result;
                        });
                        
                        console.log('Phone field initialized with intlTelInput');
                    } catch (error) {
                        console.error('Error initializing intlTelInput:', error);
                    }
                } else {
                    // Try again if not found or not initialized
                    setTimeout(initializePhoneField, 1000);
                }
            }
            
            // Function to format phone number
            function formatPhoneNumber(input, iti) {
                try {
                    var phoneNumber = input.value;
                    if (!phoneNumber) {
                        return;
                    }
                    
                    var formattedNumber = '';
                    
                    // Try to get formatted number
                    formattedNumber = iti.getNumber();
                    
                    // If couldn't format or invalid, try manual formatting
                    if (!formattedNumber || !iti.isValidNumber()) {
                        var countryData = iti.getSelectedCountryData();
                        var dialCode = countryData.dialCode || '';
                        
                        // Egyptian number format: 01xxxxxxxxx
                        if (countryData.iso2 === 'eg' && phoneNumber.match(/^0[1][0-9]{9}$/)) {
                            formattedNumber = '+2' + phoneNumber;
                        }
                        // Numbers starting with 0 (local format)
                        else if (phoneNumber.startsWith('0')) {
                            formattedNumber = '+' + dialCode + phoneNumber.substring(1);
                        }
                        // Numbers without international format
                        else if (!phoneNumber.startsWith('+')) {
                            formattedNumber = '+' + dialCode + phoneNumber;
                        }
                        // If already has international prefix
                        else {
                            formattedNumber = phoneNumber;
                        }
                    }
                    
                    // Update input value
                    input.value = formattedNumber;
                    
                    // Update email field with phone number
                    updateEmail(formattedNumber);
                } catch (error) {
                    console.error('Error formatting phone number:', error);
                }
            }
            
            // Function to update email field
            function updateEmail(phoneNumber) {
                try {
                    // Try to find the email field
                    var emailField = $('input[name$="email"]');
                    if (emailField.length) {
                        var cleanNumber = phoneNumber.replace(/\D/g, '');
                        var domain = window.location.hostname;
                        var email = cleanNumber + '@' + domain;
                        
                        emailField.val(email).trigger('change');
                    }
                } catch (error) {
                    console.error('Error updating email:', error);
                }
            }
            
            // Start initialization
            initializePhoneField();
        });
    };
});