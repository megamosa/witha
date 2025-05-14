define([
    'uiComponent',
    'jquery',
    'ko',
    'intlTelInput'
], function (Component, $, ko) {
    'use strict';
    
    return Component.extend({
        defaults: {
            defaultCountry: 'EG',
            preferredCountries: ['EG', 'US', 'GB', 'SA', 'AE']
        },
        
        initialize: function () {
            this._super();
            
            var self = this;
            
            // Wait for telephone field to appear
            var checkExist = setInterval(function() {
                if ($('input[name="telephone"]').length) {
                    clearInterval(checkExist);
                    self.initializePhoneInput();
                }
            }, 100);
        },
        
        initializePhoneInput: function() {
            var self = this;
            
            // Get allowed countries from Magento API
            $.ajax({
                url: '/rest/V1/directory/countries',
                type: 'GET',
                dataType: 'json',
                success: function(countries) {
                    var allowedCountries = [];
                    for (var i = 0; i < countries.length; i++) {
                        allowedCountries.push(countries[i].id);
                    }
                    
                    // Initialize on all telephone fields
                    $('input[name="telephone"]').each(function() {
                        var input = this;
                        
                        // Make sure intlTelInput is available
                        if (typeof window.intlTelInput === 'function') {
                            try {
                                // Always initialize with a default country
                                var iti = window.intlTelInput(input, {
                                    initialCountry: self.defaultCountry,
                                    preferredCountries: self.preferredCountries,
                                    onlyCountries: allowedCountries.length ? allowedCountries : undefined,
                                    separateDialCode: true,
                                    autoHideDialCode: false,
                                    nationalMode: false,
                                    utilsScript: require.toUrl('MagoArab_WithoutEmail/js/utils.js')
                                });
                                
                                // Save the instance for later
                                $(input).data('iti', iti);
                                
                                // When validation happens, make sure we use the full number
                                $(input).closest('form').on('submit', function() {
                                    if (iti.isValidNumber()) {
                                        input.value = iti.getNumber();
                                    }
                                });
                                
                                // Update KnockoutJS model when value changes
$(input).on('blur', function() {
    // Always try to get the formatted number
    var number = iti.getNumber();
    
    // If not valid or couldn't get number, format manually
    if (!number || !iti.isValidNumber()) {
        var countryData = iti.getSelectedCountryData();
        var dialCode = countryData.dialCode || '20'; // Default to Egypt
        var phoneNumber = input.value;
        
        // Format number manually
        if (phoneNumber.indexOf('+') !== 0) {
            if (phoneNumber.indexOf('0') === 0) {
                // Remove leading 0 and add country code
                number = '+' + dialCode + phoneNumber.substring(1);
            } else {
                // Just add country code
                number = '+' + dialCode + phoneNumber;
            }
        } else {
            number = phoneNumber;
        }
    }
    
    // Update the field value
    input.value = number;
    
    // Find KnockoutJS viewmodel
    var viewModel = ko.dataFor(this);
    if (viewModel && typeof viewModel.value === 'function') {
        viewModel.value(number);
    }
    
    // Also update the email field
    var domain = window.location.hostname;
    var email = number.replace(/\D/g, '') + '@' + domain;
    
    var emailField = document.querySelector('input[name$=".email"]');
    if (emailField) {
        emailField.value = email;
        $(emailField).trigger('change');
    }
});
                            } catch (error) {
                                console.error('Error initializing intlTelInput:', error);
                            }
                        }
                    });
                },
                error: function(error) {
                    console.error('Error fetching countries:', error);
                    
                    // Fallback initialization with hardcoded values
                    $('input[name="telephone"]').each(function() {
                        var input = this;
                        
                        if (typeof window.intlTelInput === 'function') {
                            try {
                                var iti = window.intlTelInput(input, {
                                    initialCountry: self.defaultCountry,
                                    preferredCountries: self.preferredCountries,
                                    separateDialCode: true,
                                    autoHideDialCode: false,
                                    nationalMode: false,
                                    utilsScript: require.toUrl('MagoArab_WithoutEmail/js/utils.js')
                                });
                                
                                $(input).data('iti', iti);
                            } catch (error) {
                                console.error('Error in fallback initialization:', error);
                            }
                        }
                    });
                }
            });
        }
    });
});