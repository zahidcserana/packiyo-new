window.checkDeleteButton = function () {
    $('.order-item-fields:not(.order-item-deleted)').length === 1 ? $('.delete-item').prop('disabled', true) : $('.delete-item').prop('disabled', false);
};

window.hideItems = function () {
    if(!$('select[name="order_id"]').val()) {

        $('#item_container, #add_item').hide();
    }
};

window.dateTimePicker = function () {
    $('.datetimepicker').daterangepicker({
        autoUpdateInput: false,
        singleDatePicker: true,
        timePicker: false,
        timePicker24Hour: false,
        autoApply: true,
        locale: {
            format: window.app.data.date_format
        }
    });

    $('.datetimepicker').on('hide.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format(window.app.data.date_format)).trigger('change');
    });

    $('.datetimepicker').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
};

window.dtDateRangePicker = function (configuration = {}) {
    $('.dt-daterangepicker').daterangepicker({
        autoUpdateInput: false,
        singleDatePicker: true,
        timePicker: false,
        timePicker24Hour: false,
        autoApply: true,
        locale: {
            cancelLabel: 'Clear',
            format: window.app.data.date_format
        },
        maxDate: configuration.maxDate ?? undefined,
    }, function(start) {
        if (this.element.hasClass('without-autofill')) {
            this.element.val(start.format(window.app.data.date_format)).trigger('change');
        }
    });

    const dateRangePickerWithoutAutofill = $('.dt-daterangepicker:not(.without-autofill)');

    dateRangePickerWithoutAutofill.on('hide.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format(window.app.data.date_format)).trigger('change');
    });

    dateRangePickerWithoutAutofill.on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('').trigger('change');
    });
};

window.deleteItemFromTableButton = function () {
    $(document).on('click', '.delete-item', function (event) {
        $(this).parent().parent().find('.reset_on_delete').val(0);
        $(this).parent().parent().hide().addClass('order-item-deleted');
        checkDeleteButton();
        event.preventDefault();
    });
}

window.appendValidationMessages = function (modal, response, starred = false) {
    let errors = response.responseJSON.errors

    clearValidationMessages(modal)

    // Append validation message to each input label
    for (const [name, message] of Object.entries(errors)) {
        let htmlName = name

        if (name.includes('.')) {
            let nameArray = name.split('.')
            htmlName = nameArray[0]

            for (let i = 1; i < nameArray.length; i++) {
                htmlName = htmlName + '[' + nameArray[i] + ']'
            }
        }

        let input = modal.find('[name="' + htmlName + '"]')

        if (input.length === 0) {
            input = modal.find('[name^="' + htmlName + '"]')
        }

        let label = input.parents('.form-group').find('label')

        if (label.length === 0) {
            label = input.parents('.form-group')
        }

        if (starred) {
            label.append(`<span class="validate-error text-danger form-error-messages"> * </span>`)
        } else {
            label.append(`
                <span class="validate-error text-danger form-error-messages">
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    ${message}
                </span>
            `)
        }

        toastr.error(message)
    }

    // Highlight tabs with validation errors
    modal.find('.nav-item > a').each(function () {
        let contentId = $(this).attr('href')

        if ($(contentId).find('.validate-error').length) {
            $(this).addClass('text-danger')
        }
    })

    // Click on first tab with validation error
    modal.find('a.text-danger').first().trigger('click')
}

window.clearValidationMessages = function (modal) {
    modal.find('span.validate-error').remove()

    modal.find('.nav-item > a.text-danger').removeClass('text-danger')
}

window.resetModalWithForm = function (modal) {
    modal.find('form')[0].reset()

    modal.find('select').val(null).trigger('change')

    modal.find('.nav-item:first-child > a').tab('show')

    clearValidationMessages(modal)

    modal.modal('hide')
}

window.isNumberic = function (value) {
    return /^-?\d+$/.test(value);
}

window.serializeFilterForm = function (filterForm) {
    let request = {}

    filterForm
        .serializeArray()
        .map(function(input) {
                const value = input.value
                const isArray = input.name.includes('[]')
                const name = input.name.replace('[]', '')

                if (isArray) {
                    if (typeof request[name] === 'undefined') {
                        request[name] = []
                    }
                    request[name].push(value)
                } else {
                    request[name] = value
                }
            }
        );

    return request
}

window.queryUrl = function (params) {
    query = $.isEmptyObject(params) ? '' : '?' + $.param(params);

    return history.pushState({},'', location.protocol + '//' + location.host + location.pathname  + query);
}

window.loadFilterFromQuery = function (filterForm) {
    const searchParams = new URLSearchParams(document.location.search)

    searchParams.forEach((value, key) => {
        const element = filterForm.find(`[name="${key}"]`)

        if (key.includes('[]')) {
            if (element.length && element[0].nodeName == 'SELECT') {
                let option = $(element).find(`option[value="${value}"]`)
                if (option.length) {
                    option.prop('selected', true)
                } else {
                    option = new Option(value, value, true, true)
                    element.append(option).trigger('change')
                }
            }
        } else {
            if (element.attr('type') == 'checkbox' && value == 1) {
                element.prop('checked', true)
            } else {
                element.val(value)
            }
        }
    })
};

window.auditLog = function (modelId) {
    if ($('#audit-log-table').length) {
        let modelName = $('#audit-log-table').data('model-name')

        window.datatables.push({
            selector: '#audit-log-table',
            resource: 'audit_log',
            ajax: {
                url: '/audit/' + modelName + '/data_table/' + modelId
            },
            order: [0, 'desc'],
            columns: [
                {
                    "title": "Date",
                    "data": "created_at",
                    "name": "created_at",
                    "class": "text-neutral-text-gray",
                },
                {
                    "title": "User",
                    "data": "user",
                    "name": "user",
                    "class": "text-neutral-text-gray",
                },
                {
                    "title": "Object",
                    "data": "object",
                    "name": "object",
                },
                {
                    "title": "Event",
                    "data": "event",
                    "name": "event"
                },
                {
                    "title": "Note",
                    "data": "message",
                    "name": "message",
                }
            ]
        });
    }
};

window.reloadAuditLog = function () {
    window.dtInstances['#audit-log-table'].ajax.reload()
}

window.clearValidationMessages = function (modal) {
    modal.find('span.validate-error').remove()

    modal.find('.nav-item > a.text-danger').removeClass('text-danger')
};

window.resetModalWithForm = function (modal) {
    modal.find('form')[0].reset()

    modal.find('select').val(null).trigger('change')

    modal.find('.nav-item:first-child > a').tab('show')

    clearValidationMessages(modal)

    modal.modal('hide')
};

window.isNumeric = function (value) {
    return /^-?\d+$/.test(value);
};

window.debounce = function (func, wait, immediate) {
    let timeout;
    return function () {
        const context = this,
            args = arguments;
        const later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

window.ajaxMessageBox = function (message, success) {
    if (success) {
        toastr.success(message)
    } else {
        toastr.error(message)
    }
};
