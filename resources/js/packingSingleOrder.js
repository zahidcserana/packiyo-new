
function printOnElectron (url, printerType = 'label-printer') {
    if (window.electron && window.electron.ipcRenderer) {
        window.electron.ipcRenderer.send('print-content', { content: url, printerType: printerType });
    }
}

window.PackingSingleOrder = function (orderId, packingNote = null, isWholesale = 0) {
    const validateConfirmButton = () => {
        if (!$('.confirm-button-group').hasClass('on-hold')) {
            const button = $('.confirm-button-group > button');
            const notices = $('.notices > span');

            if($('.confirm-button-group').hasClass('allow-partial')) {
                if (toPackTotal - packedTotal) {
                    button.removeClass('btn-blue').addClass('btn-light');
                    button.attr('disabled', 'disabled');
                } else {
                    button.removeClass('btn-light').addClass('btn-blue').removeAttr('disabled');
                    button.removeAttr('disabled');
                }
            } else {
                if (toPackTotal - packedTotal) {
                    button.removeClass('btn-blue').addClass('btn-light');
                    button.attr('disabled', 'disabled');
                    notices.addClass('d-flex').removeClass('d-none');
                } else {
                    button.removeClass('btn-light').addClass('btn-blue').removeAttr('disabled');
                    button.removeAttr('disabled');
                    notices.addClass('d-none').removeClass('d-flex');
                }
            }
        }
    }

    $('#shipping_box').select2().on('select2:open', () => {
        $(".select2-dropdown:not(:has(a))").prepend('<a data-target="#custom-package-modal" data-toggle="modal" class="select2-results__option d-block cursor-pointer text-black">Specify Custom Package</a>');
    })

    $('#custom-package-modal').on('show.bs.modal', function (e) {
        $('#shipping_box').select2('close');
    })

    $('#input-shipping_method_id').select2().on('select2:open', () => {
        $(".select2-dropdown:not(:has(a))").prepend('<a class="select2-results__option d-block cursor-pointer text-black show-calculated-rates">Show Calculated Rates</a>');
    })

    $('#shipping-rates-modal').on('show.bs.modal', function (e) {
        $('#input-shipping_method_id').select2('close');
        getShippingRates();
    })

    let packButtonClass = localStorage.getItem('pack-button-class');

    let route = $('#packing_form').attr('action')
    let success_route = $('#packing_form').attr('data-success')
    let bulkShipBatch = $('#packing_form').attr('data-bulk-ship-batch') == 'true'
    let bulkShipBatchId = $('[name="bulk_ship_batch_id"]').val()

    let toPackTotal = 0;
    let packedTotal = 0;
    let toPackNumWarning = false;
    let activePackage = 1;
    let packageCount = 1;
    let nextPackageName = 1;

    let packingState = [];
    let itemQuantityState = [];

    let packageTitle = 'Package';

    let shippingBoxHeightLocked = false;
    let shippingBoxWidthLocked = false;
    let shippingBoxLengthLocked = false;
    let triggerShippingBoxChange = false;
    let shippingBoxWeightLocked = false;

    let serialNumberInput = $('[name="serial_number"]');

    let checkBulkShipBatchProgress = false
    let showBulkShipBatchInProgressModal = false

    if (bulkShipBatchId) {
        checkBulkShipBatchProgress = true
        checkBatchStatus()
    }

    let orderShippingMethodMapping = []

    $(document).on('click', '.ship-button, .ship-and-print-button', function () {
        const title = $(this).text();

        if ($(this).hasClass('ship-button')) {
            $('#confirm-dropdown').text(title).removeClass('confirm-ship-and-print-button').addClass('confirm-ship-button');
            localStorage.setItem('pack-button-class', '.ship-button');
        } else {
            $('#confirm-dropdown').text(title).removeClass('confirm-ship-button').addClass('confirm-ship-and-print-button');
            localStorage.setItem('pack-button-class', '.ship-and-print-button');
        }
    });

    if (packButtonClass) {
        $(packButtonClass).click();
    }

    function updateItemCountInPackage() {
        for (let packageIndex = activePackage; packageIndex < packingState.length; packageIndex++) {
            const packageArr = packingState[packageIndex];
            const button = $('#show_package_' + packageIndex);
            const itemsInPackage = button.find('.items-in-package');
            const text = ' (' + packageArr.items?.length + ')';

            if (itemsInPackage.length) {
                itemsInPackage.text(text);
            } else {
                button.append('<span class="items-in-package">' + text + '</span>');
            }
        }
    }

    function drawDimensions(activePackage) {
        $('#weight').val(packingState[activePackage]['weight'] < 0 ? 0 : packingState[activePackage]['weight']);
        $('#length').val(packingState[activePackage]['_length']);
        $('#width').val(packingState[activePackage]['width']);
        $('#height').val(packingState[activePackage]['height']);

        if (shippingBoxLengthLocked) {
            $('#length').prop('readonly', true);
        } else {
            $('#length').prop('readonly', false);
        }

        if (shippingBoxWidthLocked) {
            $('#width').prop('readonly', true);
        } else {
            $('#width').prop('readonly', false);
        }

        if (shippingBoxHeightLocked) {
            $('#height').prop('readonly', true);
        } else {
            $('#height').prop('readonly', false);
        }

        if (shippingBoxWeightLocked) {
            $('#weight').prop('readonly', true);
        } else {
            $('#weight').prop('readonly', false);
        }
    }

    function packageButtonsResort() {
        let packNum = 1;
        $('#package_buttons_container .show_package').each(function () {
            $(this).html(packageTitle + ' ' + packNum);

            let xButton = $(this).next('.package-button-close');
            xButton.show();

            if (packNum == 1) {
                xButton.hide();
            }
            packNum++;
        });

        activePackage = 1;
        nextPackageName = packNum;
    }

    function firstAvailablePackage() {
        return $('#package_buttons_container .show_package:first').attr('rel');
    }

    $('#custom-package-modal').on('shown.bs.modal', () => {
        const length = $('#input-custom-package-length');
        const width = $('#input-custom-package-width');
        const height = $('#input-custom-package-height');
        const weight = $('#input-custom-package-weight');

        length.removeAttr('readonly').val(packingState[activePackage]['_length']);
        weight.removeAttr('readonly').val(packingState[activePackage]['weight']);
        width.removeAttr('readonly').val(packingState[activePackage]['width']);
        height.removeAttr('readonly').val(packingState[activePackage]['height']);
    })

    function updateUnpackedItemsTotals () {
        let items = 0;
        let weight = 0;

        $('.unpacked-items-table tbody tr:visible').each(function(){
            const item = $(this);
            const orderItemId = item.attr('rel');
            const pickedLocationId = parseInt(item.attr('picked-location-id'));
            const toteId = parseInt(item.attr('picked-tote-id'));
            const productWeight = parseFloat($('#order_item_weight_form_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val());
            const orderItemQuantity = parseInt($('#order_item_quantity_form_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val());

            items += orderItemQuantity;
            weight += (productWeight * orderItemQuantity);
        });

        $('input[name="total_unpacked_items"]').val(items);
        $('input[name="total_unpacked_weight"]').val(weight);
    }

    function saveCustomOption () {
        packingState[activePackage]['custom'] = 1;
        $('#length').val($('#input-custom-package-length').val()).trigger('change');
        $('#width').val($('#input-custom-package-width').val()).trigger('change');
        $('#height').val($('#input-custom-package-height').val()).trigger('change');
        $('#weight').val($('#input-custom-package-weight').val()).trigger('change');
        generateOptionName();
    }

    $(document).on('click', '.confirm-custom-package', () => {
        saveCustomOption();
    });

    function generateOptionName () {
        const id = packingState[activePackage]['box'];
        const custom = packingState[activePackage]['custom'];
        const select = $('#shipping_box');
        const options = select.find('option');

        options.each(function () {
            const option = $(this);

            if (option.val() === id && custom) {
                option.text('Custom Package');
            } else {
                option.text(option.attr('data-default-name'));
            }
        });

        select.select2();
    }

    $(document).on('click', '.shipping-rate-item', function () {
        pickShippingMethod($(this).attr('data-shipping-method-id'), $(this).data('shipping-method-name'));

        setShippingRatesData($(this).attr('data-rate'), $(this).attr('data-rate-id'));
    });

    $(document).on('click', '.show-calculated-rates', function () {
        updateUnpackedItemsTotals();
        validateShippingRatesRequest();
    });

    function pickShippingMethod (id, shippingMethod = null) {
        if ($('#input-shipping_method_id option[value=' + id + ']').length === 0) {
            $('#input-shipping_method_id').append('<option value="' + id + '">' + shippingMethod + '</option>')
        }

        $('select[name="shipping_method_id"]').val(id).trigger('change');

        $('#shipping-rates-modal').modal('hide');
    }

    function setShippingRatesData(rate, rateId) {
        $('input[name="rate"]').val(rate);
        $('input[name="rate_id"]').val(rateId);
    }

    function validateShippingRatesRequest () {
        let errorMessage = '';
        let packNum = 0;
        let leftToPack = toPackTotal - packedTotal;

        if (leftToPack > 0) {
            errorMessage += 'Order must be packed to calculate the shipping rates';
        } else {
            if (!$('input[name="shipping_contact_information[zip]"]').val()) {
                errorMessage += 'In order to get shipping rates, fill in the zip code field<br/>';
            }

            if (packedTotal) {
                packingState.map(function (packing, key) {
                    if (packing['items'] != undefined) {
                        packNum++;

                        if (packing.box === undefined) {
                            errorMessage += 'Shipping box required in Package ' + packNum + '<br/>';
                        }

                        if (packing.weight === undefined || packing.weight == 0 || packing.weight < 0) {
                            errorMessage += 'Shipping box Weight required in Package ' + packNum + '<br/>';
                        }

                        if (packing.height === undefined || packing.height === 0) {
                            errorMessage += 'Shipping box Height required in Package ' + packNum + '<br/>';
                        }

                        if (packing._length === undefined || packing._length === 0) {
                            errorMessage += 'Shipping box Length required in Package ' + packNum + '<br/>';
                        }

                        if (packing.width === undefined || packing.width === 0) {
                            errorMessage += 'Shipping box Width required in Package ' + packNum + '<br/>';
                        }
                    }
                });
            }
        }

        if (errorMessage) {
            app.alert('', errorMessage);
        } else {
            $('#shipping-rates-modal').modal('show');
        }
    }

    function getShippingRates() {
        $('#shipping-rates-modal .modal-content').html(`<div class="spinner">
            <img src="../../img/loading.gif">
        </div>`)

        let packingStateRE = [...packingState];
        packingStateRE.map(function (packing, key) {
                if (packing['items'] === undefined) {
                    packingStateRE.splice(key, 1);
                }
            }
        );

        packingStateRE = packingStateRE.map(el => Object.assign({}, el));
        packingStateRE.splice(0, 1);
        let packingStateString = JSON.stringify(packingStateRE);

        $('#packing_state').val(packingStateString);

        $.ajax({
            type: 'POST',
            serverSide: true,
            url: '/packing/' + orderId + '/shipping_rates',
            data: $('#packing_form').serialize(),
            success: function (data) {
                $('#shipping-rates-modal .modal-content').html(data);
            },
            error: function (response) {
                if (response.responseJSON.errors) {
                    $.each(response.responseJSON.errors, function (key, value) {
                        toastr.error(value)
                    });
                }
            }
        });
    }

    function runFunctions() {
        $(document).on('click', '.package-button-close', function () {
            let button = $(this);

            app.confirm(null, 'Are you sure you want to delete this package?', () => {
                let packageNumber = button.attr('rel');
                activePackage = packageNumber;

                do {
                    $('#package' + packageNumber + ' .order_item_row .unpack-item-button:first').click();
                } while ($('#package' + packageNumber + ' .order_item_row .unpack-item-button').length > 0);

                $('#package' + packageNumber).remove();
                $('#package_button_container_' + packageNumber).remove();

                packingState[packageNumber] = [];

                $('#show_package_' + firstAvailablePackage()).trigger('click');
                packageButtonsResort();
            });
        });

        $(document).on('click', '.show_package', function () {
            let blockNumber = $(this).attr('rel');

            for (let i = packageCount; i > 0; i--) {

                $('#package' + i).hide();
                $('#show_package_' + i).removeClass('active');
            }

            $('#package' + blockNumber).show();
            $('#show_package_' + blockNumber).addClass('active');

            activePackage = blockNumber;

            $('#shipping_box').val(packingState[activePackage]['box']).change();

            generateOptionName();
        });

        $(document).on('change', '#shipping_box', function () {
            let selectedOption = $(this).children(':selected')

            if (packingState[activePackage]['box'] != $(this).val() || triggerShippingBoxChange) {
                packingState[activePackage]['box'] = $(this).val()

                packingState[activePackage]['weight'] += selectedOption.data('weight') - packingState[activePackage]['weight_box']
                packingState[activePackage]['weight_box'] = selectedOption.data('weight');

                packingState[activePackage]['_length'] = selectedOption.data('length');
                packingState[activePackage]['width'] = selectedOption.data('width');
                packingState[activePackage]['height'] = selectedOption.data('height');
                packingState[activePackage]['custom'] = 0;

                if (selectedOption.data('height-locked') == 1) {
                    shippingBoxHeightLocked = true
                } else {
                    shippingBoxHeightLocked = false
                }

                if (selectedOption.data('length-locked') == 1) {
                    shippingBoxLengthLocked = true
                } else {
                    shippingBoxLengthLocked = false
                }

                if (selectedOption.data('width-locked') == 1) {
                    shippingBoxWidthLocked = true
                } else {
                    shippingBoxWidthLocked = false
                }

                if (selectedOption.data('weight-locked') == 1) {
                    shippingBoxWeightLocked = true
                } else {
                    shippingBoxWeightLocked = false
                }

                triggerShippingBoxChange = false;
            }

            drawDimensions(activePackage);
        });

        $(document).on('change', '#weight', function () {
            let value = $(this).val();

            if (value < 0 || value === '') {
                value = 0; $('#weight').val(value);
            }

            packingState[activePackage]['weight'] = value;
        });

        $(document).on('change', '#length', function () {
            packingState[activePackage]['_length'] = $(this).val();
        });

        $(document).on('change', '#width', function () {
            packingState[activePackage]['width'] = $(this).val();
        });

        $(document).on('change', '#height', function () {
            packingState[activePackage]['height'] = $(this).val();
        });

        $(document).on('change', '#name', function () {
            packingState[activePackage]['name'] = $(this).val();
        });

        $(document).on('change', '#address', function () {
            packingState[activePackage]['address'] = $(this).val();
        });

        $(document).on('change', '#address2', function () {
            packingState[activePackage]['address2'] = $(this).val();
        });

        $(document).on('change', '#company_name', function () {
            packingState[activePackage]['company_name'] = $(this).val();
        });

        $(document).on('change', '#company_number', function () {
            packingState[activePackage]['company_number'] = $(this).val();
        });

        $(document).on('change', '#city', function () {
            packingState[activePackage]['city'] = $(this).val();
        });

        $(document).on('change', '#zip', function () {
            packingState[activePackage]['zip'] = $(this).val();
        });

        $(document).on('change', '#country_name', function () {
            packingState[activePackage]['country_name'] = $(this).val();
        });

        $(document).on('change', '#email', function () {
            packingState[activePackage]['email'] = $(this).val();
        });

        $(document).on('change', '#phone', function () {
            packingState[activePackage]['phone'] = $(this).val();
        });

        $(document).on('change', '#shipping_method_id', function () {
            packingState[activePackage]['shipping_method'] = $(this).val();
        });

        $(document).on('click', '.unpack-item-button', function (e) {
            e.preventDefault();

            let itemRow = $(this).closest('tr');
            let orderItemId = itemRow.attr('rel');
            let locationId = 0;
            let pickedLocationId = parseInt(itemRow.attr('picked-location-id'));
            let toteId = parseInt(itemRow.attr('picked-tote-id'));
            let parentOrderItemId = itemRow.attr('parent-id');
            let packedParentKey = itemRow.attr('packed-parent-key');
            let serialNumber = itemRow.attr('serial-number');
            let idToUse = serialNumber ? prepareStringForId(serialNumber) : '';
            let activeIndex = $('.show_package.active').attr('rel');

            let newPickedNum = 0;
            let trId = 'order_item_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId;

            if (parseInt(itemRow.attr('picked-location-id')) > 0) {
                locationId = pickedLocationId;

                let pickedNumMax = parseInt($('#order_item_pick_max_' + locationId + '_' + orderItemId + '_' + toteId).val());
                let pickedNum = parseInt($('#order_item_pick_' + locationId + '_' + orderItemId + '_' + toteId).html());

                if ((pickedNum + 1) <= pickedNumMax) {
                    newPickedNum = pickedNum + 1;
                    $('#order_item_pick_' + locationId + '_' + orderItemId + '_' + toteId).html(newPickedNum);
                }
            } else {
                locationId = itemRow.attr('location');
            }

            let quantityBeginning = parseInt($('#order_item_quantity_beginning_' + locationId + '_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val());

            itemQuantityState[orderItemId][locationId + '_' + toteId]--;
            itemQuantityState[orderItemId][0]--;

            $('#packed-total-' + orderItemId).val(itemQuantityState[orderItemId][0]);

            let quantityRemaining = quantityBeginning - itemQuantityState[orderItemId][locationId + '_' + toteId];

            let optionNewText = $('#' + activePackage + '_order_item_location_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).html() + ' - ' + quantityRemaining;
            if (quantityRemaining == 1) {
                $('#item_' + orderItemId + '_locations').append($('<option>', {
                    value: locationId,
                    text: optionNewText
                }));
            }

            $('#item_' + orderItemId + '_locations' + ' option[value=' + locationId + ']').text(optionNewText);

            let beforeQuantityInThisPackage = $('#' + activePackage + '_order_item_quantity_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).val();
            let nowQuantityInThisPackage = beforeQuantityInThisPackage - 1;

            $('#' + activePackage + '_order_item_quantity_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).html(nowQuantityInThisPackage);
            $('#' + activePackage + '_order_item_quantity_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).val(nowQuantityInThisPackage);

            $('#order_item_quantity_span_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).html(parseInt($('#order_item_quantity_span_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).html()) + 1);
            $('#order_item_quantity_form_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val(parseInt($('#order_item_quantity_form_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val()) + 1);

            const index = itemLocationIndex(orderItemId, locationId, toteId, serialNumber, packedParentKey, activeIndex);

            if (index > -1) {
                let productWeight = parseFloat($('#order_item_weight_form_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val());

                let thisPackageWeight = parseFloat(packingState[activePackage]['weight']);

                if (productWeight > 0) {
                    let weight = thisPackageWeight - productWeight;
                    packingState[activePackage]['weight'] = parseFloat(weight.toFixed(4));
                }

                packingState[activePackage]['items'].splice(index, 1);
            }

            if (nowQuantityInThisPackage == 0) {
                itemRow.remove();
            }

            if (quantityRemaining > 0) {
                $('#' + trId).show();
                $('#' + trId).attr('barcode', $('#' + trId).attr('barcode').replace('//', ''));
            }

            packedTotal -= 1;

            $('#items-remain').html(toPackTotal - packedTotal > 1 ? (toPackTotal - packedTotal + ' Items Remain') : (toPackTotal - packedTotal + ' Item Remain'));
            validateConfirmButton();

            drawDimensions(activePackage);

            if (parentOrderItemId) {
                updateParentRow(parentOrderItemId);
            }

            updateItemCountInPackage();

            e.stopPropagation();
        });

        $(".order-items-locations-selection").each(function(index, element) {
            if ($(this).find('option').length === 0) {
                const currentRow = $(this).parents().eq(2)
                $(this).replaceWith('<span class="font-xs font-weight-500">No pickable locations available</span>')

                currentRow.find('td:eq(3)').hide()
            }
        });
    }

    runFunctions();

    function validatePackingForms(dontCheckPackedNum = false) {
        let leftToPack = toPackTotal - packedTotal;

        if (!dontCheckPackedNum && leftToPack) {
            toPackNumWarning = true;
            app.confirm('Packing', 'You have unpacked items. Do you want to continue?', function () {
                startShip(true);
            })
            return false;
        } else {
            toPackNumWarning = false;
        }

        let errorMessage = '';
        let result = true;
        if (parseInt($('#shipping_method_id').val()) == 0) {
            errorMessage += 'Shipping method required<br/>';
            result = false;
        } else {
            let packNum = 0;
            packingState.map(function (packing, key) {
                if (packing['items'] != undefined) {
                    packNum++;

                    if (packing.items === undefined || packing.items.length == 0) {
                        errorMessage += 'There are no items in Package ' + packNum + '<br/>';
                        result = false;
                    }
                    if (packing.box === undefined) {
                        errorMessage += 'Shipping box required in Package ' + packNum + '<br/>';
                        result = false;
                    }
                    if (packing.weight === undefined) {
                        errorMessage += 'Shipping box Weight required in Package ' + packNum + '<br/>';
                        result = false;
                    }
                    if (packing.height === undefined) {
                        errorMessage += 'Shipping box Height required in Package ' + packNum + '<br/>';
                        result = false;
                    }
                    if (packing._length === undefined) {
                        errorMessage += 'Shipping box Length required in Package ' + packNum + '<br/>';
                        result = false;
                    }
                    if (packing.width === undefined) {
                        errorMessage += 'Shipping box Width required in Package ' + packNum + '<br/>';
                        result = false;
                    }
                }
            });
        }

        if (errorMessage) {
            app.alert('', errorMessage);
        }

        return result;
    }

    function createPackageItemBlock(blockNumber) {

        packingState[blockNumber] = [];
        packingState[blockNumber]['items'] = [];

        let packageBlock = `
            <div id="package${ blockNumber }" class="package_item">
                <div>
                    <table
                        id="package_listing_${ blockNumber }"
                        class="table package-items-table packed-items-table"
                    >
                        <thead>
                            <tr>
                                <th class="col-7">Item</th>
                                <th class="col-3 ${ bulkShipBatch ? 'd-none' : '' }">Location</th>
                                <th class="col-1">Quantity</th>
                                <th class="col-1"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        `

        $('#package_container').append(packageBlock);

        let packageButton = '<li class="nav-item package_button_container position-relative pr-1" id="package_button_container_' + blockNumber + '">' +
            '<button type="button" class="show_package btn nav-link m-0 active" rel="' + blockNumber + '" id="show_package_' + blockNumber + '">' + packageTitle + ' ' + nextPackageName + '</button>' +
            '<a type="button" class="package-button-close ' + (blockNumber == 1 ? 'd-none' : '') + ' " rel="' + blockNumber + '">✖</a>' +
            '</li>';
        $('#package_buttons_container li:last').before(packageButton);

        for (let i = 1; i < blockNumber; i++) {
            $('#package' + i).hide();
            $('#show_package_' + i).removeClass('active');

            packingState[blockNumber]['box'] = packingState[i]['box'];
        }

        activePackage = blockNumber;

        packingState[blockNumber]['weight'] = 0;
        packingState[blockNumber]['weight_box'] = 0;

        triggerShippingBoxChange = true;

        $('#shipping_box').trigger('change');

        nextPackageName++;

        generateOptionName();
    }

    function itemLocationIndex(orderItemId, locationId, toteId, serialNumber, packedParentKey, activeIndex) {
        const packageArr = packingState[activeIndex];

        if (!packageArr.items) {
            return -1;
        }

        for (let arrIndex = 0; arrIndex < packageArr.items.length; arrIndex++) {
            const object = packageArr.items[arrIndex];

            if (
                object.orderItem === orderItemId &&
                object.location === locationId &&
                object.tote === toteId &&
                object.serialNumber === serialNumber &&
                object.packedParentKey === packedParentKey
            ) {
                return arrIndex;
            }
        }

        return -1;
    }

    function sizingAdjustments() {
        if ($(window).width() > 1200) {
            $('.navbar-top').hide();
        } else {
            $('.navbar-top').show();
        }
    }

    const packingLabelsButton = $('.packing-labels-button');

    function startLoader() {
        packingLabelsButton.attr('disabled', 'disabled').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    }

    function stopLoader() {
        packingLabelsButton.removeAttr('disabled').removeClass('btn-light').addClass('btn-blue').html('Packing Labels');
    }

    let packingLabelsResponse = null;

    function getPackingLabels(url, maxAttempts, attempt = 0) {
        startLoader();

        $.ajax({
            url: url,
            timeout: 3000,
            success: function(data) {
                if (data.success) {
                    handleSuccess(data);
                } else {
                    retryRequest();
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                if (textStatus === "timeout") {
                    retryRequest();
                } else {
                    toastr.error('Cannot get packing labels: ' + `Error: ${xhr.status} ${errorThrown}\nResponse: ${xhr.responseText}`);
                }
            }
        }).always(stopLoader);

        function retryRequest() {
            if (attempt < maxAttempts) {
                startLoader();
                setTimeout(() => getPackingLabels(url, maxAttempts, attempt + 1), 1000);
            } else {
                toastr.error('Max retry attempts reached.');
            }
        }

        function handleSuccess(data) {
            toastr.success('Packing labels prepared.');

            if ($('#input-printer_id').val()) {
                window.location.href = success_route;
            } else {
                packingLabelsResponse = data.asn.packing_labels;
            }
        }
    }

    function handleGettingLabels(labelsResponse) {
        toastr.warning('Getting packing labels..');

        const confirmButton = $('#confirm-dropdown');

        packButtonClass = packButtonClass ?? '.ship-button';

        const confirmButtonTitle = $(packButtonClass).text();

        confirmButton.text(confirmButtonTitle);

        $('.confirm-button-group button')
            .removeClass('btn-blue')
            .addClass('btn-light')
            .attr('disabled', 'disabled');

        $('.packing-card .card-body').addClass('locked-card-body');

        showShippingLabels(labelsResponse);
        // TODO: This may not identify the specific shipment associated to the ASN.
        getPackingLabels('/packing/wholesale_shipping/edi_labels/' + labelsResponse[0].shipment_id, 10);
    }

    $(document).on('click', '.packing-labels-button', function(e){
        e.preventDefault();
        $('.done-with-packing-labels-button').removeClass('d-none');
        showPackingLabels(packingLabelsResponse);
    });

    $(document).on('click', '.done-with-packing-labels-button', function(e){
        e.preventDefault();
        window.location.href = success_route;
    });

    function showPackingLabels (packingLabelsResponse) {
        let labels = '';

        if (packingLabelsResponse.length > 0) {
            let lastLabel = packingLabelsResponse[packingLabelsResponse.length - 1];
            labels = `<a href="${lastLabel.signed_url}" target="_blank">Packing Label</a>`;
        }

        app.alert('Packing Labels', labels, '', '');
    }

    function showShippingLabels (labelsResponse) {
        if (!$('#input-printer_id').val()) {
            let links = labelsResponse.map(label =>
                `<a href="${label.url}" target="_blank">${label.name}</a><br />`
            ).join('');

            app.alert('Labels', links);
        }
    }

    function isJsonString(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }

    function checkBatchStatus() {
        let bulkShipBatchLimit = $('input[name="batch_shipping_limit"]').val();

        $.get(`/packing/bulk_shipping/bulkShipBatchProgress/${bulkShipBatchId}?limit=${bulkShipBatchLimit}` , function(response) {
            $('#bulk-ship-orders-shipped-count').text(response.statistics.total_shipped)
            $('#bulk-ship-orders-failed-count').text(response.statistics.failed)
            $('#bulk-ship-orders-remaining-count').text(response.statistics.remaining)

            $.each(response.orders, function (orderId, order) {
                if (order.status === 'Shipped') {
                    $(`#bulk-ship-orders tr[data-id="${orderId}"] > td:last-child`).html('')

                    if ($(`#bulk-ship-orders tr[data-id="${orderId}"] select`).length) {
                        $(`#bulk-ship-orders tr[data-id="${ orderId }"] > td:nth-child(2)`).html(
                            $(`#bulk-ship-orders tr[data-id="${ orderId }"] > td:nth-child(2) select > :selected`).text()
                        )
                    }
                }

                if (order.status === 'Failed' && order.status_message != null) {
                    let statusResponse = order.status_message

                    if (isJsonString(statusResponse)) {
                        let status = JSON.parse(statusResponse)

                        // TODO: standartize
                        // EP
                        if (status.error && status.error.message) {
                            statusResponse = status.error.message
                        // Tribird
                        } else if (status.errors) {
                            statusResponse = status.errors.join(', ')
                        }
                    }

                    $(`#bulk-ship-orders tr[data-id="${orderId}"] .bulk-ship-order-status`)
                        .text(order.status)
                        .attr('title', statusResponse)
                        .append('<span class="order-bulk-failed-message" data-text="' + statusResponse + '"><i class="picon-info-light icon-orange"></i></span>')
                } else {
                    $(`#bulk-ship-orders tr[data-id="${orderId}"] .bulk-ship-order-status`)
                        .text(
                            response.limit_reached && order.status === 'On queue'
                                ? 'Skip'
                                : order.status
                        )
                        .attr('title', order.status_message)
                }
            })

            if (response?.labels || response?.documents) {
                checkBulkShipBatchProgress = false
                showLabelModal(response);
            } else if (response.processed && showBulkShipBatchInProgressModal) {
                showBatchInProgressModal(response)
                checkBulkShipBatchProgress = false
            }

            if (checkBulkShipBatchProgress) {
                window.setTimeout(checkBatchStatus, 2000)
            }
        })
    }

    function showBatchInProgressModal(response) {
        let removedFailedBatchOrdersUrl = $('#packing_form').attr('data-removed-failed-batch-orders-route')

        let batchStatistics = `
            <table class="mx-auto">
                <tbody class="text-left">
                    <tr>
                        <td>Total orders requested:</td>
                        <td class="pl-5 text-right">${response.statistics.requested}</td>
                    </tr>
                    <tr>
                        <td>Total orders failed:</td>
                        <td class="pl-5 text-right">${response.statistics.failed}</td>
                    </tr>
                    <tr>
                        <td>Total orders shipped:</td>
                        <td class="pl-5 text-right">${response.statistics.shipped}</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Remaining orders:</td>
                        <td class="pl-5 text-right">${response.statistics.remaining}</td>
                    </tr>
                </tbody>
            </table>
        `

        app.confirm(
            'Batch status',
            batchStatistics,
            function () {
                $.ajax({
                    method: 'POST',
                    url: removedFailedBatchOrdersUrl,
                    success: function (response) {
                        toastr.success(response.message)

                        $.each(response.failedOrdersIds, function (i, orderId) {
                            $(`#bulk-ship-orders tr[data-id="${orderId}"]`).remove()
                        })

                        if (response.labels) {
                            showLabelModal(response)
                        }
                    }
                })
            },
            'Close and merge',
            function () {
                $('#confirm-dropdown')
                    .prop('disabled', false)
                    .text('Ship order')
            },
            'Continue batch',
            'd-none'
        )
    }

    function showLabelModal(response) {
        const printerId = $('#input-printer_id').val();
        if (printerId && printerId != 'pdf') {
            window.location.href = success_route;
        } else {
            let labels = '';

            if (response?.labels) {
                for (const label of response.labels) {
                    printOnElectron(label.url);
                    labels += '<a href="' + label.url + '" target="_blank">' + label.name + '</a><br />';
                }
            }

            if (response?.documents) {
                for (const label of response.documents) {
                    printOnElectron(label.url, 'document-printer');
                    labels += '<a href="' + label.url + '" target="_blank">' + label.name + '</a><br />';
                }
            }

            if (window.electron && window.electron.ipcRenderer) {
                window.location.href = success_route + '?showToast=true&message=Labels were sent to the Rabot printers.';
            } else {
                app.alert('Labels', labels, function() {
                    window.location.href = success_route;
                }, '');
            }
        }
    }

    function startShip(dontCheckPackedNum = false) {
        const confirmButton = $('#confirm-dropdown');
        const title = confirmButton.text();

        confirmButton.text('Processing, please wait...');
        confirmButton.prop('disabled', true);

        let validate = validatePackingForms(dontCheckPackedNum);
        if (validate) {
            let packingStateRE = [...packingState];
            packingStateRE.map(function (packing, key) {
                    if (packing['items'] == undefined) {
                        packingStateRE.splice(key, 1);
                    }
                }
            );

            packingStateRE = packingStateRE.map(el => Object.assign({}, el));
            packingStateRE.splice(0, 1);
            let packingStateString = JSON.stringify(packingStateRE);

            $('#packing_state').val(packingStateString);

            $('#order-shipping-method-mappings').val(JSON.stringify(orderShippingMethodMapping))

            if ($('#input-printer_id').val() == 'pdf') {
                $('#input-printer_id').val(null);
            }

            $.ajax({
                url: route,
                type: 'POST',
                dataType: 'json',
                data: $('#packing_form').serialize(),
                success: function (response) {
                    if (isWholesale) {
                        handleGettingLabels(response.labels);
                    } else {
                        if (response.bulkShipOrderJobsDispatched) {
                            showBulkShipBatchInProgressModal = true;
                            return;
                        }

                        if ($('#input-printer_id').val()) {
                            window.location.href = success_route;
                        } else {
                            let labels = '';

                            if(response?.labels) {
                                for (const label of response.labels) {
                                    labels += '<a href="' + label.url + '" target="_blank">' + label.name + '</a><br />';
                                }
                            }

                            if(response?.documents) {
                                for (const label of response.documents) {
                                    labels += '<a href="' + label.url + '" target="_blank">' + label.name + '</a><br />';
                                }
                            }

                            app.alert('Labels', labels, function() {
                                window.location.href = success_route;
                            }, '');
                        }

                        showLabelModal(response);

                        if (bulkShipBatchId && checkBulkShipBatchProgress == false) {
                            checkBulkShipBatchProgress = true;
                            checkBatchStatus();
                        }

                        return;
                    }
                },
                error: function (errorResponse) {
                    let errorsTitle = ''
                    let errors = []

                    if (typeof errorResponse.responseJSON !== 'undefined') {
                        if (typeof errorResponse.responseJSON.message !== 'undefined') {
                            errorsTitle = errorResponse.responseJSON.message
                        }

                        if (typeof errorResponse.responseJSON.errors !== 'undefined') {
                            $.each(errorResponse.responseJSON.errors, function (key, value) {
                                $.each(value, function (i, error) {
                                    errors.push(error)
                                })
                            })
                        }
                    } else {
                        errors.push('Cannot process the shipping. Please try with different shipping method.')
                    }

                    app.alert(errorsTitle, errors.join('<br />'))

                    confirmButton.text(title)
                    confirmButton.prop('disabled', false)
                }
            });
        } else {
            confirmButton.text(title);
            confirmButton.prop('disabled', false);
        }
    }

    function setCountryCode(country) {
        $.ajax({
            type: 'GET',
            serverSide: true,
            url: '/site/getCountryCode',
            data: {
                'country': country
            },
            success: function(response) {
                $('#cont_info_country_code').text(response.results.country_code)
            }
        })
    }

    function addParentRow (parentOrderItemId) {
        let exist = false;
        const packageContainer = $('.package_item:visible tbody');
        const parentRow = packageContainer.find('.parent-row[data-kit-parent-id="' + parentOrderItemId + '"]');

        if (!parentRow.length) {
            const parentTitle = $('.unpacked-items-table tr[rel="' + parentOrderItemId + '"] td:first-child .row div:last-child span:first-child').text();
            packageContainer.append('<tr class="parent-row" data-kit-parent-id="' + parentOrderItemId + '">' +
                '<td><span class="font-md font-weight-600">' + parentTitle + '</span></td>' +
                '<td></td>' +
                '<td class="align-middle text-center col col-1"><span class="font-md font-weight-600"></span></td>' +
                '<td>' +
                '</td>' +
                '</tr>');
        } else {
            exist = true;
        }

        return exist;
    }

    function updateParentRow (parentOrderItemId) {
        const packedContainer = $('.package_item:visible tbody');
        const parentRow = packedContainer.find('tr[data-kit-parent-id="' + parentOrderItemId + '"]');

        const parentItem = $('.unpacked-items-table tr[rel="' + parentOrderItemId + '"]');
        const childItems = $('.unpacked-items-table tr[parent-id="' + parentOrderItemId + '"]');
        let totalQuantityPacked = 0;
        const kitsArr = [];
        let hideParent = true;

        childItems.each(function(){
            const id = $(this).attr('rel');
            const packPerKit = parseInt(parentItem.attr('to-pack-per-kit-' + id));
            const childRows = packedContainer.find('tr[rel="' + id + '"]');
            hideParent = !$(this).is(':visible');

            if (childRows.length) {
                let itemsQuantityPacked = 0;

                childRows.each(function(){
                    const quantityPacked = parseInt($(this).find('td:eq(2) span').text());
                    itemsQuantityPacked += quantityPacked
                });

                totalQuantityPacked += itemsQuantityPacked;
                kitsArr.push(Math.floor(itemsQuantityPacked / packPerKit));
            } else {
                kitsArr.push(0);
            }
        });

        if (hideParent) {
            parentItem.hide();
        } else {
            parentItem.show();
        }

        const totalKits = Math.min(...kitsArr);

        if (totalQuantityPacked) {
            parentRow.find('td:eq(2)').html('<span class="font-md font-weight-600">' + (!totalKits ? '' : (totalKits + (totalKits > 1 ? ' Kits' : ' Kit'))) + '</span>');
        } else {
            parentRow.remove();
        }
    }

    var generatedIds = {};

    function prepareStringForId(str) {
        if (generatedIds[str]) {
            return generatedIds[str];
        }

        var cleanedStr = str.replace(/[^a-zA-Z0-9]+/g, '-');

        cleanedStr = cleanedStr.replace(/^-+|-+$/g, '');

        var uniqueStr = cleanedStr;
        var count = 1;

        while (Object.values(generatedIds).includes(uniqueStr)) {
            uniqueStr = cleanedStr + '-' + count++;
        }

        generatedIds[str] = uniqueStr;

        return uniqueStr;
    }

    $(document).ready(function () {
        if (packingNote && packingNote.trim().length) {
            app.alert('Packing note', packingNote)
        }

        $('select').on('select2:select', function (e) {
            $(':focus').blur()
        });

        let barcode = ""

        let productBarcodes = []
        let shippingBoxBarcodes = []
        let snapCodeActionsBarcodes = [
            'PACKIYO_SHIP_ORDER', // print label
            'PACKIYO_SHIP_PRINT_ORDER', // print label and order slip
            'PACKIYO_ADD_PACKAGE', // new package
        ]

        $(document).on('keypress', function(event) {
            if (event.keyCode == 13) {
                return false
            }

            barcode += $.trim(event.key).toUpperCase();

            if (window.barcodeFlushTimeout) {
                window.clearTimeout(window.barcodeFlushTimeout)
            }

            window.barcodeFlushTimeout = window.setTimeout(function() {
                if (isSnapCodeBarcode(barcode)) {
                    handleSnapCodeBarcode(barcode)
                }

                barcode = ''
            }, 100)
        })

        $('#items_listing tr.order_item_row').each(function () {
            let product = $(this).attr('product')

            productBarcodes[$(this).attr('barcode')] = {'product': product, 'quantity': 1}

            $.each(JSON.parse($(this).attr('barcodes')), function (barcode, quantity) {
                productBarcodes[barcode] = {'product': product, 'quantity': quantity}
            })
        })

        $('select#shipping_box option').each(function () {
            shippingBoxBarcodes.push($(this).data('barcode'))
        })

        function handleSnapCodeBarcode(barcode) {
            if (Object.keys(productBarcodes).includes(barcode)) {
                let quantity = parseInt(productBarcodes[barcode].quantity)
                quantity = quantity == 0 ? 1 : quantity
                let itemRowId = $('#items_listing tbody > tr:visible[product=' + productBarcodes[barcode].product + ']:first').attr('id')

                packItemsByClick(itemRowId, quantity)
            } else if (shippingBoxBarcodes.includes(barcode)) {
                let box = $(`select#shipping_box option[data-barcode="${ barcode }"]`).val()

                $('select#shipping_box').val(box)
                $('select#shipping_box').trigger('change')
            }

            if (snapCodeActionsBarcodes.includes(barcode) && $('#confirm-dropdown').prop('disabled') === false) {
                switch (barcode) {
                    case 'PACKIYO_SHIP_ORDER':
                        $('button.ship-button').click()
                        $('#confirm-dropdown').click()
                        break

                    case 'PACKIYO_SHIP_PRINT_ORDER':
                        $('button.ship-and-print-button').click()
                        $('#confirm-dropdown').click()
                        break

                    case 'PACKIYO_ADD_PACKAGE':
                        $('#add_package').click()
                        break
                }
            }
        }

        function isSnapCodeBarcode(barcode) {
            return Object.keys(productBarcodes).includes(barcode)
                || shippingBoxBarcodes.includes(barcode)
                || snapCodeActionsBarcodes.includes(barcode)
        }

        $('.shipping_contact_info_set').click(function () {
            $('#cont_info_name').html($('#input-shipping_contact_information\\[name\\]').val());
            $('#cont_info_company_name').html($('#input-shipping_contact_information\\[company_name\\]').val());
            $('#cont_info_company_number').html($('#input-shipping_contact_information\\[company_number\\]').val());
            $('#cont_info_address').html($('#input-shipping_contact_information\\[address\\]').val());
            $('#cont_info_address2').html($('#input-shipping_contact_information\\[address2\\]').val());
            $('#cont_info_zip').html($('#input-shipping_contact_information\\[zip\\]').val());
            $('#cont_info_state').html($('#input-shipping_contact_information\\[state\\]').val());
            $('#cont_info_city').html($('#input-shipping_contact_information\\[city\\]').val());
            $('#cont_info_email').html($('#input-shipping_contact_information\\[email\\]').val());
            $('#cont_info_phone').html($('#input-shipping_contact_information\\[phone\\]').val());
            $('#cont_info_country_name').text($('[name="shipping_contact_information[country_id]"]').select2('data')[0].text);
            $('#cont_info_country_code').text($('[name="shipping_contact_information[country_id]"]').select2('data')[0].country_code);
        });

        let packedItemsObj = [];

        $('#add_package').click(function () {
            packageCount++;
            createPackageItemBlock(packageCount);
        });

        function packItem(itemRow, serialNumber = '') {
            let hideRow = false;
            let orderItemId = itemRow.attr('rel');
            let locationId = 0;
            let locationName = '';
            let pickedLocationId = parseInt(itemRow.attr('picked-location-id'));
            let toteId = parseInt(itemRow.attr('picked-tote-id'));
            let toteName = itemRow.attr('picked-tote-name');
            let newPickedNum = 0;
            let parentOrderItemId = itemRow.attr('parent-id');
            let packedParentKey = '';
            let activeIndex = $('.show_package.active').attr('rel');

            let packedRow = $('#' + activePackage + '_order_item_' + locationId + '_' + orderItemId + packedParentKey);

            if (pickedLocationId > 0) {
                locationId = parseInt(itemRow.attr('picked-location-id'));
                locationName = itemRow.attr('picked-location-name');
            } else {
                locationId = $('#item_' + orderItemId + '_locations' + ' option').filter(':selected').val();
                locationName = $('#item_' + orderItemId + '_locations' + ' option:selected').text();
                locationName = locationName.substr(0, locationName.indexOf(' - '));
            }

            let orderItemLocationObj = {
                orderItem: orderItemId,
                location: locationId,
                tote: toteId,
                serialNumber: serialNumber,
                parentId: parentOrderItemId,
                packedParentKey: packedParentKey
            };

            let itemExistsInPackage = itemLocationIndex(orderItemId, locationId, toteId, serialNumber, packedParentKey, activeIndex);

            let orderItemKey = itemRow.attr('key');
            let productWeight = parseFloat($('#order_item_weight_form_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val());
            let thisPackageWeight = parseFloat(packingState[activePackage]['weight']);
            let quantityBeginning = parseInt($('#order_item_quantity_beginning_' + locationId + '_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val());

            if (itemQuantityState[orderItemId] == undefined) {
                itemQuantityState[orderItemId] = [];
                itemQuantityState[orderItemId][locationId + '_' + toteId] = 0;
                itemQuantityState[orderItemId][0] = 0;
            } else if (itemQuantityState[orderItemId][locationId + '_' + toteId] == undefined) {
                itemQuantityState[orderItemId][locationId + '_' + toteId] = 0;
            }

            if (parseInt($('#to-pack-total-' + orderItemId).val()) > itemQuantityState[orderItemId][0]) {
                if (parseInt(itemRow.attr('picked-location-id')) > 0) {
                    let pickedNum = parseInt($('#order_item_pick_' + locationId + '_' + orderItemId + '_' + toteId).html());
                    newPickedNum = pickedNum - 1;
                    $('#order_item_pick_' + locationId + '_' + orderItemId + '_' + toteId).html(newPickedNum);
                    if (newPickedNum == 0) {
                        hideRow = true;
                    }
                } else {
                    let foundPickedLocation = false;
                    $('.picked_' + orderItemId).each(function() {
                        if (parseInt($(this).html()) > 0) {
                            foundPickedLocation = true;
                        }
                    });

                    if (foundPickedLocation) {
                        app.alert('Packing', 'Please pack the items from picked locations first.')
                        return;
                    }
                }

                let beforeQuantityInThisPackage = 0;
                itemQuantityState[orderItemId][0]++;
                itemQuantityState[orderItemId][locationId + '_' + toteId]++;
                $('#packed-total-' + orderItemId).val(itemQuantityState[orderItemId][0]);
                let quantityRemaining = quantityBeginning - itemQuantityState[orderItemId][locationId + '_' + toteId];
                let quantityRemainingGlobal = $('#to-pack-total-' + orderItemId).val() - $('#packed-total-' + orderItemId).val();
                let idToUse = serialNumber ? prepareStringForId(serialNumber) : '';
                if (itemExistsInPackage === -1) {
                    let rowHtml = itemRow[0].outerHTML;

                    rowHtml = rowHtml.replace(/LOCATION-ID/g, locationId);
                    rowHtml = rowHtml.replace(/TOTE-ID/g, toteId > 0 ? toteId : '');
                    rowHtml = rowHtml.replace(/SERIAL-NUMBER/g, idToUse);

                    rowHtml = rowHtml.replace('order_item_location_span_' + locationId + '_' + orderItemId, activePackage + '_order_item_location_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_picked_span_' + locationId + '_' + orderItemId, activePackage + '_order_item_picked_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_' + locationId + '_' + orderItemId, activePackage + '_order_item_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_quantity_span_' + locationId + '_' + orderItemId, activePackage + '_order_item_quantity_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_quantity_form_' + locationId + '_' + orderItemId, activePackage + '_order_item_quantity_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_unpack_' + locationId + '_' + orderItemId, activePackage + '_order_item_unpack_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_id_form_' + locationId + '_' + orderItemId, activePackage + '_order_item_id_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_location_form_' + locationId + '_' + orderItemId, activePackage + '_order_item_location_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_tote_form_' + locationId + '_' + orderItemId, activePackage + '_order_item_tote_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_weight_form_' + locationId + '_' + orderItemId, activePackage + '_order_item_weight_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse);
                    rowHtml = rowHtml.replace('order_item_serial_number_' + locationId + '_' + orderItemId, activePackage + '_order_item_serial_number_' + locationId + '_' + orderItemId + packedParentKey + idToUse);

                    packedRow = $(rowHtml);

                    if (serialNumber) {
                        packedRow.find('.order_item_serial_number').text(`S/N: ${serialNumber}`);
                    }

                    if (parentOrderItemId) {
                        if (addParentRow(parentOrderItemId)) {
                            $('#package_listing_' + activePackage + ' tbody tr[parent-id="' + parentOrderItemId + '"]:last').after(packedRow)
                        } else {
                            $('#package_listing_' + activePackage + ' tbody').append(packedRow);
                        }
                    } else {
                        $('#package_listing_' + activePackage + ' tbody').append(packedRow);
                    }

                    packedRow.attr('package', activePackage);
                    packedRow.attr('packed-parent-key', packedParentKey);
                    packedRow.attr('serial-number', serialNumber);
                } else {
                    beforeQuantityInThisPackage = parseInt($('#' + activePackage + '_order_item_quantity_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).val());
                }

                let nowQuantityInThisPackage = beforeQuantityInThisPackage + 1;

                $('#' + activePackage + '_order_item_location_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).html((toteName != '' ? toteName + ' - ' : '') + locationName);
                $('#' + activePackage + '_order_item_picked_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse).hide();

                $('#' + activePackage + '_order_item_quantity_span_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).html(nowQuantityInThisPackage);
                $('#' + activePackage + '_order_item_quantity_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).val(nowQuantityInThisPackage);

                $('#' + activePackage + '_order_item_unpack_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).removeClass('d-none');

                $('#order_item_quantity_span_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).html(toteId == 0 ? quantityRemainingGlobal : newPickedNum);

                $('#order_item_quantity_form_LOCATION-ID_' + orderItemId + '_' + pickedLocationId + '_' + toteId).val(toteId == 0 ? quantityRemainingGlobal : newPickedNum);

                if (quantityRemaining == 0) {
                    $('#item_' + orderItemId + '_locations' + ' option[value=' + locationId + ']').remove();
                }

                if (quantityRemainingGlobal == 0) {
                    hideRow = true;
                }

                let keyName = orderItemKey + '_' + orderItemId + packedParentKey + '_' + idToUse + '_' + locationId + '_' + toteId + '_' + activePackage;

                $('#' + activePackage + '_order_item_quantity_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).attr('name', 'order_items[' + keyName + '][quantity]');
                $('#' + activePackage + '_order_item_id_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).attr('name', 'order_items[' + keyName + '][order_item_id]');
                $('#' + activePackage + '_order_item_location_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).attr('name', 'order_items[' + keyName + '][location_id]');
                $('#' + activePackage + '_order_item_tote_form_' + locationId + '_' + orderItemId + packedParentKey + idToUse + '_' + pickedLocationId + '_' + toteId).attr('name', 'order_items[' + keyName + '][tote_id]');

                packingState[activePackage]['items'].push(orderItemLocationObj);

                if (productWeight > 0) {
                    packingState[activePackage]['weight'] = parseFloat(thisPackageWeight.toFixed(4)) + parseFloat(productWeight.toFixed(4));
                }

                packedTotal += 1;

                $('#items-remain').html(toPackTotal - packedTotal > 1 ? (toPackTotal - packedTotal + ' Items Remain') : (toPackTotal - packedTotal + ' Item Remain'));
                validateConfirmButton();

                let optionNewText = locationName + ' - ' + quantityRemaining;
                $('#item_' + orderItemId + '_locations' + ' option[value=' + locationId + ']').text(optionNewText);
            } else {
                app.alert(null, 'You packed all items of this product');
            }

            if (hideRow) {
                itemRow.hide();
                itemRow.attr('barcode', '//' + itemRow.attr('barcode'));
            }

            drawDimensions(activePackage);

            if (parentOrderItemId) {
                updateParentRow(parentOrderItemId);
            }

            updateItemCountInPackage();
        }

        $('.pack-item-button').click(function (event) {
            let itemRow = $(this).closest('tr');


            if (itemRow.attr('has-serial-number') == 1) {
                serialNumberInput.val('');

                const modal = $('#pack-item-serial-number-input-modal');

                modal.modal('show');
                modal.data('item-row', itemRow)
            } else {
                packItem(itemRow);
            }
        });

        // Packing all / in quantities

        $('.pack-all-items-button').click(function () {
            let itemRowId = $(this).closest('tr').attr('id')

            toastr.warning('Packing is in progress..');

            // Bug fix for the strange behaviour of dropdown
            $('#package_container .dropdown.show').removeClass('show')
            $('#package_container .dropdown-menu.show').removeClass('show')

            setTimeout(function(){
                packItemsByClick(itemRowId);
            }, 350);
        })

        $('#pack-in-quantities-modal').on('shown.bs.modal', (e) => {
            let itemRow = $(e.relatedTarget).closest('tr')

            $('#input-quantity_to_pack')
                .val(1)
                .attr('max', parseInt(itemRow.find('[id^=order_item_quantity_span_LOCATION]').text()))
            $('#pack-in-quantities-submit').attr(
                'data-item-row-id',
                itemRow.attr('id')
            )
        })

        $(document).on('click', '#pack-in-quantities-submit', () => {
            let itemRowId = $('#pack-in-quantities-submit').attr('data-item-row-id')
            let quantity = $('#input-quantity_to_pack').val()

            packItemsByClick(itemRowId, quantity)
        })

        function packItemsByClick(itemRowId, quantity = false) {
            let itemRow = $(`[id="${ itemRowId }"`)
            let maxQuantity = parseInt(itemRow.find('[id^=order_item_quantity_span_LOCATION]').text())

            if (! quantity || quantity > maxQuantity) {
                quantity = maxQuantity
            }

            for (quantity; quantity > 0; quantity--) {
                itemRow.find('.pack-item-button').click()
            }
        }

        // Unpacking all in quantities

        $(document).on('click', '.unpack-all-items-button', function (){
            let itemRowId = $(this).closest('tr').attr('id')

            unpackItemsByClick(itemRowId)
        })

        $('#unpack-in-quantities-modal').on('shown.bs.modal', (e) => {
            let itemRow = $(e.relatedTarget).closest('tr')

            $('#input-quantity_to_unpack')
                .val(1)
                .attr('max', parseInt(itemRow.find('[id*=_order_item_quantity_span_]').text()))
            $('#unpack-in-quantities-submit').attr(
                'data-item-row-id',
                itemRow.attr('id')
            )
        })

        $(document).on('click', '#unpack-in-quantities-submit', () => {
            let itemRowId = $('#unpack-in-quantities-submit').attr('data-item-row-id')
            let quantity = $('#input-quantity_to_unpack').val()

            unpackItemsByClick(itemRowId, quantity)
        })

        function unpackItemsByClick(itemRowId, quantity = false) {
            let itemRow = $(`[id="${ itemRowId }"`)
            let maxQuantity = parseInt(itemRow.find('[id*=_order_item_quantity_span_]').text())

            if (! quantity || quantity > maxQuantity) {
                quantity = maxQuantity
            }

            for (quantity; quantity > 0; quantity--) {
                itemRow.find('.unpack-item-button').click()
            }
        }

        $('#pack-item-serial-number-input-modal').on('shown.bs.modal', () => {
            serialNumberInput.focus();
        });

        $('#serial-number-set-button').click(function (event) {
            let itemRow = $('#pack-item-serial-number-input-modal').data('item-row');
            let serialNumber = serialNumberInput.val().trim();

            if (!serialNumber) {
                toastr.error('No serial number input, product is not packed');
            } else if ($(`[serial-number="${serialNumber}"]`).length) {
                toastr.error('Serial number already used!');
            } else {
                packItem(itemRow, serialNumber);
            }

            $('#barcode').focus();
        });

        serialNumberInput.keydown(function (event) {
            if (event.keyCode === 13) {
                $('#serial-number-set-button').click();
                event.preventDefault();
            }
        });

        $('.confirm-ship-button, .confirm-ship-and-print-button').click(function () {
            let printPackingSlip = $('[name="print_packing_slip"]');

            if ($(this).hasClass('confirm-ship-and-print-button')) {
                printPackingSlip.val(true);
            } else {
                printPackingSlip.val(null);
            }

            if ($('[name="shipping_method_id"]').val() == 'generic') {
                app.confirm(null, 'Are you sure you want to ship using generic label?', startShip, null, null, null);
            } else {
                startShip();
            }
        });

        createPackageItemBlock(packageCount);

        $('.sidenav-toggler').removeClass('active');
        $('.sidenav-toggler').data('action', 'sidenav-pin');
        $('body').removeClass('g-sidenav-pinned').removeClass('g-sidenav-show').addClass('g-sidenav-hidden');
        $('body').find('.backdrop').remove();

        sizingAdjustments()

        $(window).resize(function () {
            sizingAdjustments();
        })

        $('.to-pack-total:not(.to-pack-total-skip-calculation)').each(function () {
            toPackTotal += parseInt($(this).val());
        })

        $('#items-in-order').html(toPackTotal > 1 ? (toPackTotal + ' Items in Order') : (toPackTotal + ' Item in Order'));
        $('#items-remain').html(toPackTotal > 1 ? (toPackTotal + ' Items Remain') : (toPackTotal + ' Item Remain'));

        $('#shipping_box').trigger('change')

        const dropPointSelect = $('.drop_point_id')
        const dropPointBaseUrl = dropPointSelect.data('ajax--url')

        $('#select-drop-point-modal').on('shown.bs.modal', function () {
            let dropPointLocatorData = dropPointBaseUrl
                + '?zip=' + $('#cont_info_zip').text()
                + '&city=' + $('#cont_info_city').text()
                + '&address=' + $('#cont_info_address').text()
                + '&country_code=' + $('#cont_info_country_code').text()
                + '&order_id=' + orderId
                + '&shipping_method_id=' + $('[name="shipping_method_id"]').val()

            dropPointSelect.select2('destroy')
            dropPointSelect.data('ajax--url', dropPointLocatorData)
            dropPointSelect.select2({
                dropdownParent: $(this)
            })
        })

        $('.drop_point_id').on('select2:select', function (e) {
            $('#drop_point_id').val($(this).val())
            $('#drop-point-info').attr('hidden', false)
            $('#drop-point-details').text(e.params.data.text)
        })

        if ($('#input-shipping_method_id').val() !== undefined) {
            checkDropPointsForShippingMethod()
        }

        $('#input-shipping_method_id').on('change', function () {
            checkDropPointsForShippingMethod()

            setShippingRatesData(null, null)
        })

        function checkDropPointsForShippingMethod() {
            let method = $('#input-shipping_method_id').val()
            const requireDropPoint = $('#check-drop-point-' + method + '').val()

            if (requireDropPoint === '1') {
                $('#drop-point-modal').attr('hidden', false)

                const dropPointData = $('#select-drop-points-button')

                if (dropPointData.data('shipping-method-name') !== 'null') {
                    let dropPointSelect = $('.drop_point_id')

                    let dropPointAjax = dropPointSelect.data('ajax--url')
                        + '?zip=' + $('#cont_info_zip').text()
                        + '&city=' + $('#cont_info_city').text()
                        + '&address=' + $('#cont_info_address').text()
                        + '&country_code=' + $('#cont_info_country_code').text()
                        + '&order_id=' + orderId
                        + '&preselect=' + true
                        + '&shipping_method_id=' + $('[name="shipping_method_id"]').val()

                    $.ajax({
                        type: 'GET',
                        serverSide: true,
                        url: dropPointAjax,
                        success: function(response) {
                            if (response.results.length > 0) {
                                const initialDropPoint = response.results[0]

                                $('#drop_point_id').val(initialDropPoint.id)
                                $('#drop-point-info').attr('hidden', false)
                                $('#drop-point-details').text(initialDropPoint.text)
                            }
                        }
                    })
                }

            } else {
                $('#drop-point-modal').attr('hidden', true)
                $('#drop-point-info').attr('hidden', true)
            }
        }
    });

    $('.bulk-ship-order-status').on('click', '.order-bulk-failed-message', function () {
        app.alert('Order status', $(this).data('text'))
    })
};
