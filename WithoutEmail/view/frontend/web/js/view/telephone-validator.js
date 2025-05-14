define([
    'jquery',
    'ko',
    'uiComponent',
    'intlTelInput'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagoArab_WithoutEmail/form/element/telephone'
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            
            // Initialize the phone input after render
            this.initPhoneInput();
            
            return this;
        },
        
        /**
         * Initialize the international phone input
         */
        initPhoneInput: function() {
            var self = this;
            
            // Use setTimeout to ensure the element is rendered
            setTimeout(function() {
                var phoneInput = document.getElementById('telephone');
                if (phoneInput) {
                    try {
                        var iti = window.intlTelInput(phoneInput, {
                            initialCountry: "EG",
                            preferredCountries: ["EG", "US", "GB", "SA", "AE"],
                            separateDialCode: true,
                            nationalMode: true,
                            utilsScript: require.toUrl('MagoArab_WithoutEmail/js/utils.js')
                        });
                        
                        // Format on blur
                        $(phoneInput).on('blur', function() {
                            try {
                                var phoneNumber = phoneInput.value;
                                if (phoneNumber) {
                                    var formattedNumber = iti.getNumber();
                                    if (formattedNumber) {
                                        phoneInput.value = formattedNumber;
                                    }
                                }
                            } catch (error) {
                                console.log("Error formatting phone number:", error);
                            }
                        });
                    } catch (error) {
                        console.log("Error initializing intlTelInput:", error);
                    }
                }
            }, 1000);
        }
    });
});