window.ShippingCarrier = function () {
    const filterForm = $('#toggleFilterForm').find('form')

    function setDeleteButton(){
        $('.del_icon').click(function(){
            $('#del_button_' + $(this).attr("rel")).trigger("click");
        });
    }

    window.datatables.push({
        selector: '#shipping-carrier-table',
        resource: 'shipping-carrier',
        ajax: {
            url: '/shipping_carrier/data-table/',
            data: function(data){
                let request = {}
                filterForm
                    .serializeArray()
                    .map(function(input) {
                        request[input.name] = input.value;
                    });

                data.filter_form = request
                data.from_date = $('#shipping-carrier-table-date-filter').val();
            }
        },
        order: [1, "desc"],
        columns: [
            {
                "non_hiddable":true,
                "orderable": false,
                "class": "text-left edit-icon",
                "title": "Action",
                "name": "edit",
                "data": function (data) {
                    if (data.link_edit) {
                        return `
                            <a type="button" class="svg-btn" data-id="${data.id}" href="${data.link_edit}">
                                <i class="picon-edit-filled icon-lg" title="Edit"></i>&nbsp;
                            </a> <i class="picon-sync-filled icon-lg ` + (data.active == true ? "text-success" : "sync-btn") + `" title="Sync" data-id="${data.id}"></i>&nbsp;
                            ` + (data.active == true ? '<i class="picon-wifi-off-filled text-success disconnection-btn icon-lg" title="Disconnect" href="#" data-toggle="modal" data-target="#carrierDisconnectionModal" data-id="' + data.id + '"></i>' : '<i class="picon-wifi-off-filled icon-lg" title="Disconnect"></i>') + `
                        `
                    }

                    return null
                },
            },
            {
                "title": "Carrier",
                "data": "name",
                "name": "name",
            },
            {
                "title": "Carrier Account",
                "data": "carrier_account",
                "name": "carrier_account"
            },
            {
                "title": "Integration",
                "data": "integration",
                "name": "integration"
            }
        ]
    })

    $(document).ready(function() {
        let availableInputTypes = 'input:text, input:hidden, input:password, input:file, select, textarea';

        $(document).on('click', '.add-carrier-btn', function(e) {
            $('.configuration-item').remove();

            $.get('/shipping_carrier/tribird', function(res, status){
                let html = ``;

                res.forEach(function(item) {
                    html += `<div class="col-3 pt-2">
                        <div class="custom-radio text-left pl-5">
                            <input name="carrier_type" class="custom-control-input carrier-item" id="${item.name}" type="radio" value="${item.type}">
                            <label class="custom-control-label text-black font-weight-600 text-sm" for="${item.name}">${item.name}</label>
                        </div>
                    </div>`;
                });

                $('.carrier-list').html(html);

                if (res.length == 0) {
                    toastr.error("There is an error connecting the carriers server. Please try again later!");
                }
            });
        });

        $(document).on('click', '.carrier-item', function(e) {
            let carrierType = $(this).val();
            let index = 0;

            if (carrierType) {
                $.get("/shipping_carrier/tribird/" + carrierType, function(res, status){
                    $('.configuration-item').remove();
                    let html = ``;

                    $.map(res.data.filter(item => item.setup_field == true), function(data, key) {
                        html += reformConfigurationField(data, index);

                        index++;
                    });

                    $(".carrier-configurations").append(html);
                });
            }
        });

        $(document).on('submit', '#carrierCreateForm', function (e) {
            e.preventDefault()

            let errorFields = [];

            $('#carrierListModal').find(availableInputTypes).filter(function() {
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

                if((checkVal == '' || checkVal == null) && attName == 'customer_id') {
                    errorFields.push(attName)
                }
            });

            if(errorFields.length > 0) {
                toastrErrorFieldsCheck(errorFields);
            } else {
                $('#submitCreateButton').prop('disabled', true);
                $(".loading-img-div").removeClass('d-none')

                let url = $(this).attr('action')
                let data = $(this).serialize()

                $.ajax({
                    type: "POST",
                    url: url,
                    data: data,
                    success: function (response) {
                        $(".loading-img-div").addClass('d-none')
                        $('#submitCreateButton').prop('disabled', false);
                        $('#carrierListModal').modal('hide')

                        toastr.success(response.message)
                        window.dtInstances['#shipping-carrier-table'].ajax.reload()
                    },
                    error: function (response) {
                        if (response.responseJSON) {
                            $(".loading-img-div").addClass('d-none')
                            $('#submitCreateButton').prop('disabled', false);

                            $.each(response.responseJSON, function (key, value) {
                                toastr.error(value)
                            });
                        }
                    }
                });
            }
        })

        $(document).on('click', '.disconnection-btn', function(e) {
            $('#disconnectionText').val("");
            $('#carrierDisconnectionForm').attr('action', 'shipping_carrier/' + $(this).data("id") + '/disconnect');
        });

        $(document).on('submit', '#carrierDisconnectionForm', function (e) {
            e.preventDefault()

            let url = $(this).attr('action')
            let data = $(this).serialize()

            $.ajax({
                type: "POST",
                url: url,
                data: data,
                success: function (response) {
                    toastr.success(response.message)
                    window.dtInstances['#shipping-carrier-table'].ajax.reload()
                    $('#carrierDisconnectionModal').modal('hide')
                },
                error: function (response) {
                    if (response.responseJSON) {
                        $.each(response.responseJSON, function (key, value) {
                            toastr.error(value)
                        });
                    }
                }
            });
        })

        $(document).on('click', '.sync-btn', function(e) {
            $.ajax({
                type: "GET",
                url: 'shipping_carrier/' + $(this).data("id") + '/connect',
                success: function (response) {
                    toastr.success(response.message)
                    window.dtInstances['#shipping-carrier-table'].ajax.reload()
                },
                error: function (response) {
                    if (response.responseJSON) {
                        $.each(response.responseJSON, function (key, value) {
                            toastr.error(value)
                        });
                    }
                }
            });
        });

        $(document).on('click', '.close', function(e) {
            $('.configuration-item').remove();
        });

        function openCreationModal() {
            const carrierListModal = $('#carrierListModal')

            carrierListModal.find('.searchSelect .customer_id').select2({
                dropdownParent: carrierListModal
            })
        }

        function reformConfigurationField(data, index)
        {
            return data.type != 'Checkbox' ? getTextInputHtml(data, index) : getCheckboxInputHtml(data, index);
        }

        function getTextInputHtml(data, index) {
            if (data.type === 'Textarea') {
                return `<div class="col-12 configuration-item">
                    <div class="form-group mb-0 mx-2 text-left mb-3 d-flex flex-column">
                        <input type="hidden" name="configurations[${index}][field]" value="${data.field}">
                        <label for="${data.field}" class="text-neutral-text-gray font-weight-600 font-xs">` + data.title + `</label>
                        <div class="input-group input-group-merge font-sm ">
                            <textarea id="${data.field}" class="configuration-value form-control font-sm font-weight-600 text-neutral-gray h-auto p-2 shipping-carrier-textarea" placeholder="Enter ${data.title}" name=configurations[${index}][value] value=""></textarea>
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
                                <option value="" disabled selected>Select ${data.title}</option>
                                ${options}
                            </select>
                        </div>
                    </div>
                </div>`;
            }
            else {
                return `<div class="col-6 configuration-item">
                    <div class="form-group mb-0 mx-2 text-left mb-3 d-flex flex-column">
                        <input type="hidden" name="configurations[${index}][field]" value="${data.field}">
                        <label for="${data.field}" class="text-neutral-text-gray font-weight-600 font-xs">` + data.title + `</label>
                        <div class="input-group input-group-merge font-sm ">
                            <input id="${data.field}" class="configuration-value form-control font-sm font-weight-600 text-neutral-gray h-auto p-2" placeholder="Enter ${data.title}" type="${data.type == 'Password' ? 'password' : 'text' }" name=configurations[${index}][value] value="" autocomplete="${data.type == 'Password' ? 'new-password' : 'off' }">
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

        $(document).on('keyup', '.shipping-carrier-textarea', function() {
            let data = $(this).val();
            let length = data.length;

            if (length > 200) {
                $(this).attr('rows','5');
            }
            else {
                $(this).attr('rows','2');
            }
        });
    });
};
