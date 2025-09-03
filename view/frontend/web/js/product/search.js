define([
    'jquery',
    'uiComponent',
    'ko',
    'domReady!'
], function ($, Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'CrimsonAgility_ProductsInRange/product/search'
        },

        initialize: function () {
            this.searchResults = ko.observableArray([]);
            this.isLoading = ko.observable(false);
            this.itemCount = ko.observable(null);
            this.productsPerPage = ko.observable(10);
            this.sortOrder = ko.observable('asc');
            this.currentPage = ko.observable(1);
            this.emptyResult = ko.observable(null);
            this.searchError = ko.observable(null);

            this._super();
        },

        submitProductSearch: function (data, e) {
            e.preventDefault();

            if (!$('#product-search-form').valid()) {
                return;
            }

            this.productSearch()
        },

        productSearch: function (page = 1) {
            var minPrice = $('#min-price').val();
            var maxPrice = $('#max-price').val();
            var sortOrder = $('#sort-order').val();
            var pageSize = $('#page-size').val() ?? 10;
            this.productsPerPage(pageSize);

            $('body').trigger('processStart');
            this.currentPage(page);
            self=this;

            $.ajax({
                url: 'https://app.crimsonagility.test/rest/V1/product/search',
                type: 'GET',
                context: this,
                dataType: 'json',
                data: {
                    minPrice: minPrice,
                    maxPrice: maxPrice,
                    direction: sortOrder,
                    pageSize: pageSize,
                    page: page
                },
                success: function (response) {
                    console.log(response);
                    self.searchResults(Object.values(response[0]));
                    self.itemCount(response[1]);
                    if (!response[1] || response[1].length === 0) {
                        self.emptyResult(true);
                    } else {
                        self.emptyResult(false);
                    }
                    $('body').trigger('processStop');
                },
                error: function () {
                    this.searchError(true);
                    $('body').trigger('processStop');
                }
            })
        },

        numberOfPages: function () {
            if (this.itemCount() == null || this.productsPerPage() == null) {
                return 0
            }

            return Math.ceil(this.itemCount() / this.productsPerPage())
        },

        pageSizeOptions: function () {
            return [
                {
                    value: 10,
                    default: true
                },
                {
                    value: 20,
                    default: false
                },
                {
                    value: 50,
                    default: false
                },

            ]
        }

    });
});
