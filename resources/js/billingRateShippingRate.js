window.BillingRateShippingRate = function () {
    $(document).ready(function() {
        let carrierSelect = $('.shipping_carrier_select[name="settings[shipping_carrier_id]"]');
        let countryFilter = $('.countries-filter-input');
        let countryList = $('.all-countries-list');
        let addShippingZoneButton = $('#add-shipping-zone');
        let addShippingPriceRowButton = $('.add-shipping-price-row');

        let methodFilter = $('#methods-filter-input');
        let selectedMethodsList = $('#selected-methods-list');
        let selectedMethodsListField = $('#selected-methods-field');
        let allMethodsList = $('#all-methods-list');

        carrierSelect.on('change', function () {
            let selectedCarrier = carrierSelect.val();

            allMethodsList.empty();
            selectedMethodsList.empty();
            selectedMethodsListField.empty();

            generateShippingMethods(selectedCarrier);
        }).trigger('ajaxSelectOldValueUrl:toggle');

        methodFilter.on('keyup', function () {
            let query = $(this).val().toLowerCase();
            allMethodsList.find('.all-methods-list-group-item').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(query) > -1)
            });
        });

        $(document).on('click', '.all-methods-list-group-item', function (event) {
            event.preventDefault();
            event.stopPropagation();

            let methodRow = $(this);
            let clonedRow = methodRow.clone();
            clonedRow.removeClass('all-methods-list-group-item');
            clonedRow.addClass('selected-methods-list-group-item');
            selectedMethodsList.append(clonedRow);
            selectedMethodsList.find('.empty-line').addClass('d-none');
            selectedMethodsListField.find('option[value="' + clonedRow.data('method_id') + '"]').attr('selected', 'selected');
            methodRow.addClass('d-none');
        });

        $(document).on('click', '.selected-methods-list-group-item:not(.empty-line)', function (event) {
            event.preventDefault();
            event.stopPropagation();

            let methodRow = $(this);
            $(document).find('.all-methods-list-group-item[data-method_id="' + methodRow.data('method_id') + '"]').removeClass('d-none');
            selectedMethodsListField.find('option[value="' + methodRow.data('method_id') + '"]').attr('selected', false);
            methodRow.remove();
            if (selectedMethodsList.children().length <= 1) {
                selectedMethodsList.find('.empty-line').removeClass('d-none');
            }
        });

        function generateShippingMethods(selectedCarrier) {
            $.get("/shipping_carriers/" + selectedCarrier + "/shipping_methods", function(data, status){
                $.map( data, function(result) {
                    for (let i = 0; i < result.length; i++) {
                        let method = result[i];
                        selectedMethodsListField.append(new Option(method.text, method.id, false, false));

                        let anchor = document.createElement("a")
                        anchor.innerHTML = method.text;
                        anchor.href = "";
                        anchor.className = "all-methods-list-group-item list-group-item-action";
                        anchor.setAttribute("data-method_id", method.id);
                        allMethodsList.append(anchor);
                    }
                })
            });
        }

        $(document).on('click', '.remove-shipping-price-item', function () {
            let shippingZone = $(this).closest('.shipping-zone');

            $(this).parents('.shipping-price-row').remove();
            resetZonesAndLinePositions();
            toggleRemoveButtonDisplay(shippingZone);
        });

        $(document).on('click', '.remove-shipping-zone', function () {
            $(this).parents('.shipping-zone').remove();
            resetZonesAndLinePositions();
        });

        countryFilter.on('keyup', function () {
            let query = $(this).val().toLowerCase();
            countryList.find('.list-group-item').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(query) > -1)
            });
        });

        countryList.find('.list-group-item').on('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            let countryRow = $(this);
            let clonedCountryRow = countryRow.clone();
            clonedCountryRow = clonedCountryRow.removeClass('all-countries-country-id-' + clonedCountryRow.data('country_id'));

            countryRow.closest('.country-list-selector').find('.selected-countries-list').append(clonedCountryRow.clone());
            countryRow.closest('.country-list-selector').find('.selected-countries-list').find('.empty-line').addClass('d-none');
            countryRow.closest('.shipping-zone').find('.selected-countries-field').find('option[value="' + countryRow.data('country_id') + '"]').attr('selected', 'selected');

            $('.all-countries-country-id-' + countryRow.data('country_id')).addClass('d-none');
        });

        $(document).on('click', '.list-group-item:not(.empty-line)', function (event) {
            event.preventDefault();
            event.stopPropagation();

            let countryRow = $(this);
            $('.all-countries-list').find('.list-group-item[data-country_id="' + countryRow.data('country_id') + '"]').removeClass('d-none');
            removeCountryRow(countryRow);
        });

        addShippingZoneButton.on('click', function () {
            let shippingZones = $('.shipping-zone');

            let clonedShippingZone = shippingZones.last().clone(true, true);

            let selectedCountries = clonedShippingZone.find('.selected-countries-list').find('.list-group-item');

            for (let i = 0; i < selectedCountries.length; i++) {
                if (i === 0) { continue; }

                let countryRow = $(selectedCountries[i]);
                removeCountryRow(countryRow);
            }

            clonedShippingZone.find('.shipping-price-row').find('input').val(0);
            clonedShippingZone.find('.shipping-price-row:not(:last)').remove();
            clonedShippingZone.find('.shipping-zone-name').val("Zone - " + (parseInt(shippingZones.length) + 1));

            clonedShippingZone.insertAfter(shippingZones.last());

            countryFilter = $('.countries-filter-input');
            countryList = $('.all-countries-list');

            resetZonesAndLinePositions();
        });

        addShippingPriceRowButton.on('click', function () {
            let shippingPriceRows = $(this).closest('.shipping-zone').find('.shipping-price-row');

            let shippingPriceRow = shippingPriceRows.last().clone();

            shippingPriceRow.find('input').val(0);

            shippingPriceRow.insertAfter(shippingPriceRows.last());
            resetZonesAndLinePositions();
            toggleRemoveButtonDisplay($(this).closest('.shipping-zone'));
        });

        $('.shipping-zone-title').bind('click', function() {
            $(this).attr('contentEditable', true);
        });
    });

    function removeCountryRow(countryRow) {
        countryRow.closest('.shipping-zone').find('.selected-countries-field').find('option[value="' + countryRow.data('country_id') + '"]').attr('selected', false);
        if (countryRow.closest('.country-list-selector').find('.selected-countries-list').children().length <= 2) {
            countryRow.closest('.country-list-selector').find('.selected-countries-list').find('.empty-line').removeClass('d-none');
        }
        countryRow.remove();
    }

    function resetZonesAndLinePositions() {
        let shippingZones = $('.shipping-zone');
        shippingZones.each(function (zoneIndex) {
            let shippingZoneNameField = shippingZones.eq(zoneIndex).find('.shipping-zone-name');
            let shippingZoneNameTemplate = 'settings[shipping_zones][' + zoneIndex + '][name]';
            shippingZoneNameField.attr({
                id: 'input-' + shippingZoneNameTemplate,
                name: shippingZoneNameTemplate
            });

            let selectedCountryField = shippingZones.eq(zoneIndex).find('.selected-countries-field');
            let countryNameTemplate = 'settings[shipping_zones][' + zoneIndex + '][countries][]';
            selectedCountryField.attr({
                id: 'input-' + countryNameTemplate,
                name: countryNameTemplate
            });

            let shippingPriceRows = shippingZones.eq(zoneIndex).find('.shipping-price-row');

            shippingPriceRows.each(function (index) {
                let fields = shippingPriceRows.eq(index).find('input, select');

                for (let i = 0; i < fields.length; i++) {
                    let field = $(fields[i]);
                    let fieldName = field.attr('name').match(/\[([^\]]*)\]$/g)[0].replace(/[[()\]]/g, '');

                    if (fieldName.length > 0) {
                        let nameTemplate = 'settings[shipping_zones][' + zoneIndex + '][shipping_prices][' + index + '][' + fieldName + ']';

                        field.attr({
                            id: 'input-' + nameTemplate,
                            name: nameTemplate
                        });
                    }
                }
            });
        });
    }

    function toggleRemoveButtonDisplay($shippingZone) {
        if ($shippingZone.find('.shipping-price-row').length <= 1) {
            $shippingZone.find('.remove-shipping-price-item').hide();
        } else {
            $shippingZone.find('.remove-shipping-price-item').show();
        }
    }
};
