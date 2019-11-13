document.addEventListener('DOMContentLoaded', function() {
    var importLicenseProduct = jQuery('select#bulk__product');
    var importLicenseOrder   = jQuery('select#bulk__order');
    var addLicenseProduct    = jQuery('select#single__product');
    var addLicenseOrder      = jQuery('select#single__order');
    var addValidFor          = jQuery('input#single__valid_for');
    var addExpiresAt         = jQuery('input#single__expires_at');
    var editLicenseProduct   = jQuery('select#edit__product');
    var editLicenseOrder     = jQuery('select#edit__order');
    var editValidFor         = jQuery('input#edit__valid_for');
    var editExpiresAt        = jQuery('input#edit__expires_at');
    var bulkAddSource        = jQuery('input[type="radio"].bulk__type');

    var productDropdownSearchConfig = {
        ajax: {
            cache: true,
            delay: 500,
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: function(params) {
                return {
                    action: 'lmfwc_dropdown_search',
                    security: security.dropdownSearch,
                    term: params.term,
                    page: params.page,
                    type: 'product'
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;

                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            }
        },
        placeholder: i18n.placeholderSearchProducts,
        minimumInputLength: 1,
        allowClear: true
    };
    var orderDropdownSearchConfig = {
        ajax: {
            cache: true,
            delay: 500,
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: function(params) {
                return {
                    action: 'lmfwc_dropdown_search',
                    security: security.dropdownSearch,
                    term: params.term,
                    page: params.page,
                    type: 'shop_order'
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;

                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            }
        },
        placeholder: i18n.placeholderSearchOrders,
        minimumInputLength: 1,
        allowClear: true
    };

    if (importLicenseProduct.length > 0) {
        importLicenseProduct.select2(productDropdownSearchConfig);
    }

    if (importLicenseOrder.length > 0) {
        importLicenseOrder.select2(orderDropdownSearchConfig);
    }

    if (addLicenseProduct.length > 0) {
        addLicenseProduct.select2(productDropdownSearchConfig);
    }

    if (addLicenseOrder.length > 0) {
        addLicenseOrder.select2(orderDropdownSearchConfig);
    }

    if (addExpiresAt.length > 0) {
        addExpiresAt.datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }

    if (editLicenseProduct.length > 0) {
        editLicenseProduct.select2(productDropdownSearchConfig);
    }

    if (editLicenseOrder.length > 0) {
        editLicenseOrder.select2(orderDropdownSearchConfig);
    }

    if (editExpiresAt.length > 0) {
        editExpiresAt.datepicker({
            dateFormat: 'yy-mm-dd'
        });

        onChangeValidity(editExpiresAt, editValidFor);
    }

    if (bulkAddSource.length > 0) {
        bulkAddSource.change(function() {
            var value = jQuery('input[type="radio"].bulk__type:checked').val();

            if (value !== 'file' && value !== 'clipboard') {
                return;
            }

            // Hide the currently visible row
            jQuery('tr.bulk__source_row:visible').addClass('hidden');

            // Display the selected row
            jQuery('tr#bulk__source_' + value + '.bulk__source_row').removeClass('hidden');
        })
    }

    addExpiresAt.on('input', function() {
        onChangeValidity(addExpiresAt, addValidFor);
    });
    addExpiresAt.on('change', function() {
        onChangeValidity(addExpiresAt, addValidFor);
    });
    addValidFor.on('input', function() {
        onChangeValidity(addExpiresAt, addValidFor);
    });
    addValidFor.on('change', function() {
        onChangeValidity(addExpiresAt, addValidFor);
    });
    editExpiresAt.on('input', function() {
        onChangeValidity(editExpiresAt, editValidFor);
    });
    editExpiresAt.on('change', function() {
        onChangeValidity(editExpiresAt, editValidFor);
    });
    editValidFor.on('input', function() {
        onChangeValidity(editExpiresAt, editValidFor);
    });
    editValidFor.on('change', function() {
        onChangeValidity(editExpiresAt, editValidFor);
    });

    function onChangeValidity(expiresAt, validFor) {
        if (expiresAt.val() && !validFor.val()) {
            expiresAt.prop('disabled', false);
            validFor.prop('disabled', true);
            return;
        }

        if (!expiresAt.val() && validFor.val()) {
            expiresAt.prop('disabled', true);
            validFor.prop('disabled', false);
            return;
        }

        if (!expiresAt.val() && !validFor.val()) {
            expiresAt.prop('disabled', false);
            validFor.prop('disabled', false);
        }
    }
});