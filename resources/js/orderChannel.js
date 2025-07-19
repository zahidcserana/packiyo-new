window.OrderChannel = function () {
    $(document).ready(function() {
        dateTimePicker();
        dtDateRangePicker();

        let availableInputTypes = 'input:text, input:hidden, input:password, input:file, select, textarea';
        let borderClasses = 'border rounded borderOrange';

        $(document).on('click', '.add-order-channel-btn', function(e) {
            $('.configuration-item').remove();

            $.get('/order_channels/available', function(res, status){
                let html = ``;

                res.forEach(function(item) {
                    html += `<div class="col-4 col-sm-3 col-md-2 order-channel-item" data-type="${item.type}" data-oauth-connection="${item.oauth_connection}">
                        <div class="card">
                            <a href="#">
                                <img class="card-img-top" src="${item.image_url}" width="100" alt="${item.name} image">
                                <div class="card-body text-center py-0">
                                    <h5 class="card-title">${item.name}</h5>
                                </div>
                            </a>
                        </div>
                    </div>`;
                });

                $('.order-channel-list').html(html);

                if (res.length == 0) {
                    toastr.error("There is an error connecting the order channels server. Please try again later!");
                }
            });
        });

        $(document).on('click', '.order-channel-item', function(e) {
            $('.card').removeClass(borderClasses);
            $(this).find('.card').addClass(borderClasses);

            let type = $(this).data("type");
            let isOauthConnection = $('input[name=skipoauth]').val() == 'true' ? false : $(this).data("oauth-connection");

            $('input[name=name]').val("");
            $('input[name=order_channel_type]').val(type);
            $('input[name=oauth_connection]').val(isOauthConnection);

            let index = 0;

            if (type) {
                $.get("/order_channels/types/" + type, function(res, status){
                    $('.configuration-item').remove();
                    let html = ``;

                    $.map(res.data.filter(item => item.setup_field == true), function(data, key) {
                        html += reformConfigurationField(data, isOauthConnection, index);

                        index++;
                    });

                    $(".order-channel-configurations").append(html);
                });
            }
        });

        $(document).on('submit', '#orderChannelConnectionCreateForm', function (e) {
            e.preventDefault()

            let errorFields = [];

            $('#orderChannelListModalBody').find(availableInputTypes).filter(function() {
                return !($(this).attr("name").startsWith("configurations") && ($(this).attr("type") != 'hidden'));
            }).each(function() {
                let checkVal = null;
                let attName = null;

                if ($(this).attr("name").startsWith("configurations")) {
                    if ($(this).attr("type") == 'hidden') {
                        attName = $(this).attr("value");
                        checkVal = $(this).closest(".form-group").find(".configuration-value").val();
                    }
                } else {
                    attName = $(this).attr("name");
                    checkVal = $(this).val();
                }

                if((checkVal == '' || checkVal == null) && (attName == 'customer_id' || attName == 'name')) {
                    errorFields.push(attName)
                }
            });

            let customerId = $('input[name=customer_id]').val();
            if (customerId == '' || customerId == null) {
                errorFields.push("customer_id")
            }

            if(errorFields.length > 0) {
                toastrErrorFieldsCheck(errorFields);
            } else {
                $('#submitCreateButton').prop('disabled', true);
                $(".loading-img-div").removeClass('d-none');

                let url = $(this).attr('action')
                let data = $(this).serialize()

                if ($('#oauth-connection').attr('value') == "true") {
                    console.log('1');
                    $.get("/order_channels/check_name/" + customerId +"/" + $('input[name=name]').val(), function(res, status) {
                        console.log('2');
                        if (res.success != true) {
                            $.get("/order_channels/get_oauth_url?"+ data, function(res, status) {
                                if (res.success == true) {
                                    window.open("/order_channels/connect_commerce_with_oauth?" + data)
                                }
                            });
                        }
                    });
                }
                sendConnectRequest(url, data);
            }
        })

        function sendConnectRequest(url, data) {
            $.ajax({
                type: "POST",
                url: url,
                data: data,
                success: function (response) {
                    if (response.success) {
                        $(".loading-img-div").addClass('d-none');
                        $('#submitCreateButton').prop('disabled', false);
                        $('#orderChannelListModal').modal('hide');

                        toastr.success(response.message)

                        setTimeout(function () { document.location.reload(true); }, 2000);
                    } else {
                        $(".loading-img-div").addClass('d-none');
                        $('#submitCreateButton').prop('disabled', false);
                        $('#orderChannelListModal').modal('hide');

                        toastr.error(response.message)

                        setTimeout(function () { document.location.reload(true); }, 4000);
                    }
                },
                error: function (response) {
                    if (response.responseJSON) {
                        $(".loading-img-div").addClass('d-none');
                        $('#submitCreateButton').prop('disabled', false);

                        $.each(response.responseJSON.errors, function (key, value) {
                            toastr.error(value)
                        });
                    }
                }
            });
        }

        $(document).on('click', '.close', function(e) {
            $('.configuration-item').remove();
        });

        $(document).on('click', '#products-sync-button', function(e) {
            $(".products-sync-loading-img-div").removeClass('d-none')

            sendOrderChannelDataSyncRequest("POST", '/order_channels/' + $(this).attr('data-order-channel-id') + '/sync_products', "products-sync-loading-img-div")
        });

        $(document).on('click', '#inventories-sync-button', function(e) {
            $(".inventories-sync-loading-img-div").removeClass('d-none')

            sendOrderChannelDataSyncRequest("POST", '/order_channels/' + $(this).attr('data-order-channel-id') + '/sync_inventories', "inventories-sync-loading-img-div")
        });

        $(document).on('click', '#order-sync-by-number-button', function(e) {
            let errorFields = [];
            let orderNumber = $('input[name=order_number]').val();

            if (orderNumber == '' || orderNumber == null) {
                errorFields.push("order_number")
            }

            if (errorFields.length > 0) {
                toastrErrorFieldsCheck(errorFields);
            } else {
                $(".order-sync-by-number-loading-img-div").removeClass('d-none')

                sendOrderChannelDataSyncRequest("POST", '/order_channels/' + $(this).attr('data-order-channel-id') + '/sync_order_by_number/' + orderNumber.replace("#", "%23"), "order-sync-by-number-loading-img-div")
            }
        });

        $(document).on('click', '#order-sync-date-from-button', function(e) {
            let dateFrom = moment($('#input-order_sync_date_from').val() + ' 00:00:00').format('YYYY-MM-DD HH:mm:ss');

            $(".order-sync-date-from-loading-img-div").removeClass('d-none')

            sendOrderChannelDataSyncRequest("POST", '/order_channels/' + $(this).attr('data-order-channel-id') + '/sync_orders_by_date/' + dateFrom, "order-sync-date-from-loading-img-div")
        });

        $(document).on('click', '#shipment-sync-button', function(e) {
            let dateFrom = moment($('#input-shipment_sync_date_from').val() + ' 00:00:00').format('YYYY-MM-DD HH:mm:ss');

            $(".shipment-sync-loading-img-div").removeClass('d-none')

            sendOrderChannelDataSyncRequest("POST", '/order_channels/' + $(this).attr('data-order-channel-id') + '/sync_shipments/' + dateFrom, "shipment-sync-loading-img-div")
        });

        $(document).on('click', '#product-sync-by-id-button', function(e) {
            let errorFields = [];
            let productId = $('input[name=product_id]').val();

            if (productId == '' || productId == null) {
                errorFields.push("product_id")
            }

            if (errorFields.length > 0) {
                toastrErrorFieldsCheck(errorFields);
            } else {
                $(".product-sync-by-id-loading-img-div").removeClass('d-none')

                sendOrderChannelDataSyncRequest("POST", '/order_channels/' + $(this).attr('data-order-channel-id') + '/sync_product_by_product_id/' + productId, "product-sync-by-id-loading-img-div")
            }
        });

        $(document).on('click', '#product-sync-by-sku-button', function(e) {
            let errorFields = [];
            let productSku = $('input[name=product_sku]').val();

            if (productSku == '' || productSku == null) {
                errorFields.push("productSku")
            }

            if (errorFields.length > 0) {
                toastrErrorFieldsCheck(errorFields);
            } else {
                $(".product-sync-by-sku-loading-img-div").removeClass('d-none')

                sendOrderChannelDataSyncRequest("POST", '/order_channels/' + $(this).attr('data-order-channel-id') + '/sync_product_by_product_sku/' + productSku, "product-sync-by-sku-loading-img-div")
            }
        });

        $(".cron-checkbox input:checkbox").change(function() {
            var ischecked= $(this).is(':checked');
            let errorFields = [];

            if (ischecked) {
                $(this).closest(".align-items-center").find(availableInputTypes).filter(function() {
                    return $(this).attr("name").startsWith("cron_");
                }).each(function() {
                    let attName = $(this).closest(".align-items-center").find(".cron-expression").attr("name");
                    checkVal = $(this).closest(".align-items-center").find(".cron-expression").val();

                    if (checkVal == '' || checkVal == null) {
                        errorFields.push(attName)
                    }
                });
            }

            if(errorFields.length > 0) {
                $(this).prop('checked', false);

                toastrErrorFieldsCheck(errorFields);
            } else {
                $(this).closest('form').submit();
            }
        });

        function sendOrderChannelDataSyncRequest(requestType, requestUrl, loadingImageDivClassName) {
            $.ajax({
                type: requestType,
                url: requestUrl,
                success: function (response) {
                    $(`.${loadingImageDivClassName}`).addClass('d-none')

                    if (response.success) {
                        toastr.success(response.message)
                    } else {
                        toastr.error(response.message)
                    }
                }
            });
        }

        function openCreationModal() {
            const orderChannelListModal = $('#orderChannelListModal')

            orderChannelListModal.find('.searchSelect .customer_id').select2({
                dropdownParent: orderChannelListModal
            })
        }

        openCreationModal();

        function toastrErrorFieldsCheck(errorFields)
        {
            for (let index = 0, len = errorFields.length; index < len; ++index) {
                const element = errorFields[index];
                const elementReplace = element.replace('_', ' ')
                const element2 = elementReplace.charAt(0).toUpperCase() + elementReplace.slice(1)
                toastr.error(element2 + ' is requred!');
            }
        }

        function reformConfigurationField(data, isOauthConnection, index)
        {
            if (isOauthConnection == true && data.required_on_oauth_connection == true) {
                return data.type != 'Checkbox' ? getTextInputHtml(data, index) : getCheckboxInputHtml(data, index);
            } else if (isOauthConnection == false) {
                return data.type != 'Checkbox' ? getTextInputHtml(data, index) : getCheckboxInputHtml(data, index);
            }

            return "";
        }

        function getTextInputHtml(data, index) {
            if (data.type === 'Textarea') {
                return `<div class="col-12 configuration-item">
                    <div class="form-group mb-0 mx-2 text-left mb-3 d-flex flex-column">
                        <input type="hidden" name="configurations[${index}][field]" value="${data.field}">
                        <label for="${data.field}" class="text-neutral-text-gray font-weight-600 font-xs">` + data.title + `</label>
                        <div class="input-group input-group-merge font-sm ">
                            <textarea id="${data.field}" class="configuration-value form-control font-sm font-weight-600 text-neutral-gray h-auto p-2 order-channel-textarea" placeholder="Enter ${data.title}" name=configurations[${index}][value] value="" rows="1"></textarea>
                        </div>
                    </div>
                </div>`;
            } else if (data.type === 'Dropdown') {
                let options = '';

                if (data.options) {
                    $.each(data.options.split(','), function(index, value) {
                        value = value.trim();
                        options += `<option value="${value}" ${value === data.default_value ? 'selected' : ''}>${value.replaceAll('_', ' ').toLowerCase()}</option>`;
                    });
                }

                return `<div class="col-6 configuration-item">
                    <div class="form-group mb-0 mx-2 text-left mb-3 d-flex flex-column">
                        <input type="hidden" name="configurations[${index}][field]" value="${data.field}">
                        <label for="${data.field}" class="text-neutral-text-gray font-weight-600 font-xs">` + data.title + `</label>
                        <div class="input-group input-group-merge font-sm ">
                            <select id="${data.field}" class="configuration-value form-control font-sm font-weight-600 text-neutral-gray h-auto p-2 text-capitalize order-channel-select" name=configurations[${index}][value] value="">
                                <option>Select ${data.title}</option>
                                ${options}
                            </select>
                        </div>
                    </div>
                </div>`;
            } else {
                return `<div class="col-6 configuration-item">
                    <div class="form-group mb-0 mx-2 text-left mb-3 d-flex flex-column">
                        <input type="hidden" name="configurations[${index}][field]" value="${data.field}">
                        <label for="${data.field}" class="text-neutral-text-gray font-weight-600 font-xs">` + data.title + `</label>
                        <div class="input-group input-group-merge font-sm ">
                            <input id="${data.field}" class="configuration-value form-control font-sm font-weight-600 text-neutral-gray h-auto p-2" placeholder="Enter ${data.title}" type="${data.type == 'Password' ? 'password' : 'text' }" name=configurations[${index}][value] value="${data.default_value === null ? '' : data.default_value}" autocomplete="${data.type == 'Password' ? 'new-password' : 'off' }">
                        </div>
                    </div>
                </div>`;
            }
        }

        function getCheckboxInputHtml(data, index) {
            return `<div class="col-12 configuration-item">
                <div class="form-group mb-0 mx-2 text-left mb-3 d-flex flex-column">
                    <input type="hidden" name="configurations[${index}][field]" value="${data.field}">
                    <div class="custom-form-checkbox">
                        <input name="configurations[${index}][value]" value="0" type="hidden">
                        <input class="configuration-value" name="configurations[${index}][value]" id="chk-configurations[${index}][field]" type="checkbox" value="1">
                        <label class="text-neutral-text-gray font-weight-600 font-xs" for="chk-configurations[${index}][field]">${data.title}</label>
                    </div>
                </div>
            </div>`;
        }

        $(document).on('keyup', '.order-channel-textarea', function() {
            let data = $(this).val();
            let length = data.length;

            if (length > 100) {
                $(this).attr('rows', '3');
            } else {
                $(this).attr('rows', '1');
            }
        });
    });

    $(function() {
        $(document).on('click', '.enable-disable-order-channel-btn', function(event) {
            var orderChannelId = $('#channelId').val();

            app.confirm(
                'Confirm',
                'Are you sure want take this action ?',
                function () {
                    $.ajax({
                        method: 'POST',
                        url: '/order_channels/' + orderChannelId + '/enable_disable_order_channel',
                        success: function (response) {
                            toastr.success(response.message)
                            setTimeout(
                                function() {
                                    window.location.reload()
                                }, 1000)
                        },
                        error: function (response) {
                            toastr.error(response.responseJSON.message)
                        }
                    })
                }
            )

        });
    });

};
