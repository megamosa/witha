var config = {
    map: {
        '*': {
            'magoarab_checkout_email_filler': 'MagoArab_WithoutEmail/js/checkout-email-filler',
            'emailPhoneSync': 'MagoArab_WithoutEmail/js/email-phone-sync',
            'intlTelInput': 'MagoArab_WithoutEmail/js/intlTelInput-min',
            'phoneValidator': 'MagoArab_WithoutEmail/js/phone-validator',
            'checkoutPhoneValidator': 'MagoArab_WithoutEmail/js/checkout-phone-validator'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'MagoArab_WithoutEmail/js/view/shipping-mixin': true
            },
            'Magento_Ui/js/form/element/abstract': {
                'MagoArab_WithoutEmail/js/form/element/telephone-mixin': true
            }
        }
    },
    paths: {
        'intlTelInput': 'MagoArab_WithoutEmail/js/intlTelInput-min'
    },
    shim: {
        'intlTelInput': {
            deps: ['jquery']
        }
    }
};