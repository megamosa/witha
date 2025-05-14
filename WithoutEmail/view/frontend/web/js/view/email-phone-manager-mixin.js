define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data'
], function ($, ko, quote, checkoutData) {
    'use strict';

    return function (target) {
        return target.extend({
            /**
             * تغيير وظيفة Initialize لإضافة مراقب رقم الهاتف
             */
            initialize: function () {
                var result = this._super();
                
                // إعداد مراقبة رقم الهاتف
                var self = this;
                
                // استخدام MutationObserver لمراقبة إضافة حقل الهاتف
                setTimeout(function() {
                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                                // محاولة العثور على حقل الهاتف
                                var phoneField = $('input[name="telephone"]');
                                if (phoneField.length) {
                                    self.setupPhoneListener(phoneField);
                                }
                            }
                        });
                    });
                    
                    // بدء المراقبة
                    observer.observe(document.body, { childList: true, subtree: true });
                    
                    // محاولة إعداد المستمع الآن إذا كان حقل الهاتف موجودًا بالفعل
                    var phoneField = $('input[name="telephone"]');
                    if (phoneField.length) {
                        self.setupPhoneListener(phoneField);
                    }
                }, 1000);
                
                return result;
            },
            
            /**
             * إخفاء الحقل
             */
            getTemplate: function() {
                // استخدام قالب مخصص يخفي حقل البريد الإلكتروني
                return 'MagoArab_WithoutEmail/template/form/element/email';
            },
            
            /**
             * إعداد مستمع الهاتف
             */
            setupPhoneListener: function(phoneField) {
                var self = this;
                
                // إزالة المستمعين السابقين لتجنب التكرار
                phoneField.off('input.email change.email');
                
                // إضافة المستمع
                phoneField.on('input.email change.email', function() {
                    var phoneNumber = $(this).val();
                    if (phoneNumber) {
                        var domain = window.location.hostname;
                        var email = phoneNumber + '@' + domain;
                        
                        // تحديث قيمة البريد الإلكتروني
                        self.email(email);
                        checkoutData.setInputFieldEmailValue(email);
                        $('#customer-email').val(email).trigger('change');
                        
                        console.log('Email set to: ' + email);
                    }
                });
                
                // تنفيذ المستمع إذا كان رقم الهاتف موجودًا بالفعل
                if (phoneField.val()) {
                    phoneField.trigger('change.email');
                }
            }
        });
    };
});