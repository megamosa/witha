define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/model/customer'
], function ($, Component, quote, checkoutData, customer) {
    'use strict';

    return Component.extend({
        initialize: function () {
            this._super();
            this.bindPhoneEvents();
            
            // Watch for checkout form changes
            $(document).on('contentUpdated', this.bindPhoneEvents.bind(this));
        },

        bindPhoneEvents: function () {
            var self = this;
            
            // Wait for telephone input to be available
            var checkExist = setInterval(function() {
                var telephoneInput = $('input[name="telephone"]');
                if (telephoneInput.length) {
                    clearInterval(checkExist);
                    
                    // Update email immediately
                    self.updateEmail();
                    
                    // Bind to all possible events
                    telephoneInput.off('input.emailSync change.emailSync keyup.emailSync');
                    telephoneInput.on('input.emailSync change.emailSync keyup.emailSync', function() {
                        self.updateEmail();
                    });
                }
            }, 100);
        },

        updateEmail: function () {
            var telephoneInput = $('input[name="telephone"]');
            var emailInput = $('#customer-email');
            
            if (telephoneInput.length && emailInput.length) {
                var phoneValue = telephoneInput.val();
                if (phoneValue) {
                    var domain = window.location.hostname;
                    var generatedEmail = phoneValue + '@' + domain;
                    
                    // Update email field
                    emailInput.val(generatedEmail);
                    emailInput.trigger('change');
                    
                    // Update checkout data
                    checkoutData.setInputFieldEmailValue(generatedEmail);
                    checkoutData.setValidatedEmailValue(generatedEmail);
                    
                    // Update quote
                    if (!customer.isLoggedIn()) {
                        quote.guestEmail = generatedEmail;
                    }
                }
            }
        }
    });
});