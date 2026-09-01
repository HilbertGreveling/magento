/**
 * Checkout summary line for the payment-method fee, shown only when the
 * server included the "extra_fee_payment" segment in the quote totals.
 */
define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/totals'
], function (Component, totals) {
    'use strict';

    var CODE = 'extra_fee_payment';

    return Component.extend({
        defaults: {
            template: 'Vendor_ExtraFee/summary/fee'
        },

        isDisplayed: function () {
            return totals.getSegment(CODE) !== null;
        },

        getValue: function () {
            var segment = totals.getSegment(CODE);

            return segment ? this.getFormattedPrice(segment.value) : '';
        }
    });
});
