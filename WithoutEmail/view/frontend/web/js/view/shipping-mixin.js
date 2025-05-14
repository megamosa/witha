define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data'
], function ($, quote, checkoutData) {
    'use strict';

    return function (target) {
        return target.extend({
            initialize: function () {
                var result = this._super();
                this.setupPhoneEmailSync();
                return result;
            },

            setupPhoneEmailSync: function () {
                var self = this;
                
                // Watch for telephone field changes
                $(document).on('change input keyup', 'input[name="telephone"]', function() {
                    self.syncEmailWithPhone();
                });
                
                // Initial sync after delay
                setTimeout(function() {
                    self.syncEmailWithPhone();
                }, 1000);
            },

            syncEmailWithPhone: function () {
                var telephoneInput = $('input[name="telephone"]');
                var emailInput = $('#customer-email');
                
                if (telephoneInput.length && emailInput.length) {
                    var phoneValue = telephoneInput.val();
                    if (phoneValue) {
                        var domain = window.location.hostname;
                        var generatedEmail = phoneValue + '@' + domain;
                        
                        emailInput.val(generatedEmail);
                        emailInput.trigger('change');
                        
                        checkoutData.setInputFieldEmailValue(generatedEmail);
                        checkoutData.setValidatedEmailValue(generatedEmail);
                        
                        quote.guestEmail = generatedEmail;
                    }
                }
            }
        });
    };
});