define([
    'jquery',
    'intlTelInput'
], function ($) {
    'use strict';

    return function (Component) {
        return Component.extend({
            defaults: {
                phoneInitialized: false
            },

            initialize: function () {
                this._super();
                return this;
            },

            onUpdate: function () {
                this._super();
                this.initPhoneInput();
                return this;
            },

            initPhoneInput: function () {
                var self = this;
                if (this.phoneInitialized || this.index !== 'telephone') {
                    return;
                }

                // Wait for the element to be rendered
                setTimeout(function () {
                    var element = document.getElementById(self.uid);
                    if (element && typeof window.intlTelInput === 'function') {
                        try {
                            // Initialize intlTelInput on the telephone field
                            var iti = window.intlTelInput(element, {
                                initialCountry: 'EG',
                                preferredCountries: ['EG', 'US', 'GB', 'SA', 'AE'],
                                separateDialCode: true,
                                nationalMode: true,
                                formatOnDisplay: false,
                                utilsScript: require.toUrl('MagoArab_WithoutEmail/js/utils.js')
                            });
                            
                            // Store the instance
                            $(element).data('iti', iti);
                            self.phoneInitialized = true;
                            
                            // Define method to format phone number
                            self.formatPhoneNumber = function() {
                                try {
                                    var phoneNumber = element.value;
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
                                    
                                    // Save formatted number
                                    self.value(formattedNumber);
                                    
                                    // Update email field with phone number
                                    self.updateEmail(formattedNumber);
                                } catch (error) {
                                    console.error('Error formatting phone number:', error);
                                }
                            };
                            
                            // Method to update email field
                            self.updateEmail = function(phoneNumber) {
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
                            };
                        } catch (error) {
                            console.error('Error initializing intlTelInput:', error);
                        }
                    }
                }, 1000);
            }
        });
    };
});