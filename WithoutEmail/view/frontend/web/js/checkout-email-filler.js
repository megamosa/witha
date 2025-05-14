define([
    'jquery',
    'domReady!'
], function($) {
    'use strict';
    
    return function(config) {
        // عند تنفيذ
        $(function() {
            // المهمة: توليد البريد الإلكتروني من رقم الهاتف
            function setupEmailGeneration() {
                // البحث عن حقل الهاتف في جميع الطرق المحتملة
                var phoneSelectors = [
                    'input[name="telephone"]', 
                    'input[name="shippingAddress.telephone"]'
                ];
                
                // البحث عن حقل البريد الإلكتروني
                var emailSelectors = [
                    '#customer-email',
                    'input[name="username"]',
                    'input[type="email"]'
                ];
                
                // إضافة مستمعات الأحداث لحقل الهاتف
                phoneSelectors.forEach(function(selector) {
                    var $phoneFields = $(selector);
                    
                    $phoneFields.each(function() {
                        var $phone = $(this);
                        
                        // إزالة الأحداث السابقة (إذا وجدت)
                        $phone.off('input.emailgen change.emailgen keyup.emailgen');
                        
                        // إضافة أحداث جديدة
                        $phone.on('input.emailgen change.emailgen keyup.emailgen', function() {
                            var phoneValue = $phone.val();
                            
                            if (phoneValue) {
                                // توليد البريد الإلكتروني
                                var domain = window.location.hostname;
                                var email = phoneValue + '@' + domain;
                                
                                // تعيين قيمة البريد في جميع الحقول المحتملة
                                emailSelectors.forEach(function(emailSelector) {
                                    var $emailFields = $(emailSelector);
                                    
                                    $emailFields.each(function() {
                                        var $email = $(this);
                                        $email.val(email);
                                        
                                        // تنفيذ أحداث التغيير لتحديث نموذج knockout
                                        $email.trigger('change');
                                        $email.trigger('blur');
                                        
                                        // لدعم knockout
                                        if (typeof ko !== 'undefined' && ko.dataFor($email[0])) {
                                            var viewModel = ko.dataFor($email[0]);
                                            if (viewModel && viewModel.email && typeof viewModel.email === 'function') {
                                                viewModel.email(email);
                                            }
                                        }
                                    });
                                });
                                
                                console.log('Email generated: ' + email);
                            }
                        });
                        
                        // تنفيذ الحدث مباشرة إذا كان حقل الهاتف يحتوي على قيمة
                        if ($phone.val()) {
                            $phone.trigger('change.emailgen');
                        }
                    });
                });
                
                return $phoneFields && $phoneFields.length > 0;
            }
            
            // التنفيذ الأولي
            setupEmailGeneration();
            
            // إعداد مراقب DOM لتتبع حقول جديدة
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                        // إعادة تنفيذ الإعداد
                        setupEmailGeneration();
                    }
                });
            });
            
            // بدء المراقبة
            observer.observe(document.body, { 
                childList: true, 
                subtree: true 
            });
            
            // تنفيذ متكرر للتأكد من عمل الوظيفة
            var setupInterval = setInterval(function() {
                setupEmailGeneration();
            }, 2000);
            
            // إيقاف التنفيذ المتكرر بعد 30 ثانية
            setTimeout(function() {
                clearInterval(setupInterval);
            }, 30000);
            
            // الاستماع لأحداث عملية الدفع
            $(document).on('contentUpdated checkout:afterSectionUpdate checkout:afterLoad', function() {
                setupEmailGeneration();
            });
        });
    };
});