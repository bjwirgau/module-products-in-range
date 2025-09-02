define([
    'jquery'
], function ($) {
    return function (target) {
        $.validator.addMethod(
            'validate-max-price-range',
            function (value, element) {
                var minValue = parseInt($('#min-price').val());
                var maxValue = parseInt($('#max-price').val());

                return maxValue <= minValue * 5;
            },
            $.mage.__('Maximum price can be no more than 5 times the minimum price.')
        );

        $.validator.addMethod(
            'validate-min-price-range',
            function (value, element) {
                var minValue = parseInt($('#min-price').val());
                var maxValue = parseInt($('#max-price').val());

                return maxValue > minValue;
            },
            $.mage.__('Maximum price cannot be less than or equal to the minimum price.')
        )
    }
})