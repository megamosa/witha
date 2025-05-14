define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data'
], function ($, ko, Component, quote, checkoutData) {
    'use strict';

    var customerEmail = checkoutData.getInputFieldEmailValue();

    return Component.extend({
        defaults: {
            template: 'MagoArab_WithoutEmail/form/element/email',
            email: customerEmail,
            emailFocused: false
        },

        initObservable: function () {
            this._super()
                .observe(['email', 'emailFocused']);

            // مراقبة تغييرات رقم الهاتف
            var self = this;
            
            // ضبط مراقبة متكررة للعثور على حقل الهاتف عندما يتم تحميله
            var checkInterval = setInterval(function() {
                var phoneField = $('input[name="telephone"]');
                if (phoneField.length) {
                    clearInterval(checkInterval);
                    
                    // إضافة المراقبة لحقل الهاتف
                    phoneField.on('input change keyup blur', function() {
                        var phoneNumber = $(this).val();
                        if (phoneNumber) {
                            // توليد البريد الإلكتروني من رقم الهاتف
                            var domain = window.location.hostname;
                            var generatedEmail = phoneNumber + '@' + domain;
                            
                            // تحديث قيمة البريد الإلكتروني
                            self.email(generatedEmail);
                            checkoutData.setInputFieldEmailValue(generatedEmail);
                            
                            // تحديث قيمة الحقل نفسه
                            $('#customer-email').val(generatedEmail).trigger('change');
                        }
                    });
                    
                    // تنفيذ أول مرة إذا كان حقل الهاتف يحتوي بالفعل على قيمة
                    if (phoneField.val()) {
                        phoneField.trigger('change');
                    }
                }
            }, 500);
            
            return this;
        },

        emailHasChanged: function () {
            var self = this;
            
            // يتم تنفيذ هذا عندما يتم تقديم حقل البريد الإلكتروني في DOM
            setTimeout(function() {
                // التحقق مما إذا كان حقل الهاتف موجودًا وله قيمة
                var phoneField = $('input[name="telephone"]');
                if (phoneField.length && phoneField.val()) {
                    var phoneNumber = phoneField.val();
                    var domain = window.location.hostname;
                    var email = phoneNumber + '@' + domain;
                    
                    // تحديث البريد الإلكتروني
                    self.email(email);
                    checkoutData.setInputFieldEmailValue(email);
                }
            }, 500);
        }
    });
});