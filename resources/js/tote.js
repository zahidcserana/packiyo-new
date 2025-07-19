window.Tote = function (tote = '') {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm);
    const tableSelector = 'totes';
    auditLog(tote);

    function setDeleteButton() {
        $('.del_icon').click(function () {
            $('#del_button_' + $(this).attr("rel")).trigger("click");
        });
    }

    $(document).ready(function () {
        $(document).find('select:not(.custom-select)').select2();
    });

    if ($('#totes-table').length) {
        window.datatables.push({
            selector: '#'+tableSelector+'-table',
            resource: 'totes',
            ajax: {
                url: '/totes/data-table',
                data: function (data) {
                    let request = window.serializeFilterForm(filterForm)

                    data.filter_form = request

                    window.queryUrl(request)

                    window.exportFilters[tableSelector] = data
                }
            },
            order: [1, 'desc'],
            columns: [
                tables.bulkColumn(function (data) {
                    return `<a type="button" class="table-icon-button" data-id="${data.id}" href="${data.link_edit}">
                                <i class="picon-edit-filled icon-lg" title="Edit"></i>
                            </a>`
                }),
                {
                    "title": "Name",
                    "name": "totes.name",
                    "data": function (data) {
                        return `<a href="${data.link_edit}">${data.name}</a>`;
                    },
                    'visible': false,
                },
                {
                    "title": "Barcode",
                    "data": "barcode",
                    "name": "barcode",
                    "class": "text-neutral-text-gray"
                },
                {
                    "title": "Warehouse contact",
                    "data": "warehouse",
                    "name": "warehouse_contact_information.name",
                    "class": "text-neutral-text-gray"
                },
                {
                    "title": "Tote items",
                    "name": "tote_items",
                    'visible': false,
                    "orderable": false,
                    "data": function (data) {
                        return `<a href="${data.link_edit}">${data.tote_items}</a>`;
                    },
                },
                {
                    "title": "Created date",
                    "data": "created_at",
                    "name": "totes.created_at",
                    "class": "text-neutral-text-gray",
                    "visible": false
                },
                {
                    "non_hiddable": true,
                    "orderable": false,
                    "class": "text-left",
                    "title": "Print",
                    "name": "totes.id",
                    "data": function (data) {
                        return '<a href="' + data['link_print_barcode'] + '" target="_blank" class="table-icon-button"><i class="picon-printer-light icon-lg align-middle"></i></a>'
                    }
                },
                {
                    'non_hiddable': true,
                    "orderable": false,
                    "title": "",
                    "name": "totes.id",
                    "data": function (data) {
                        let deleteButton = app.tableDeleteButton(
                            `Are you sure you want to delete ${data.name}?`,
                            data.link_delete
                        );

                        let clearToteButton = '';

                        if (data.tote_items > 0) {
                            clearToteButton = app.tablePostButton(
                                `Are you sure you want to empty tote ${data.name}?`,
                                'Empty tote',
                                data.link_clear_tote
                            );
                        }

                        return deleteButton + clearToteButton;
                    }
                }
            ]
        })
    }

    if ($('#totes-item-table').length && tote) {
        window.datatables.push({
            selector: '#totes-item-table',
            resource: 'tote_order_items',
            ajax: {
                url: '/totes/tote-items-data-table/' + tote,
                data: function (data) {
                    data.from_date = $('#totes-item-table-date-filter').val();
                }
            },
            order: [1, 'desc'],
            columns: [
                {
                    "title": "Order number",
                    "data": function (data) {
                        return `<a href="${data.order.url}">${data.order.number}</a>`;
                    },
                    "name": "orders.number"
                },
                {
                    "title": "SKU",
                    "data": function (data) {
                        return `<a href="${data.product.url}">${data.product.sku}</a>`;
                    },
                    "name": "products.sku",
                    "visible": false
                },
                {
                    "title": "Quantity added",
                    "data": "quantity",
                    "name": "quantity",
                    "class": "text-neutral-text-gray",
                    "visible": false
                },
                {
                    "title": "Quantity removed",
                    "data": "quantity_removed",
                    "name": "quantity_removed",
                    "class": "text-neutral-text-gray",
                    "visible": false
                },
                {
                    "title": "Quantity remaining",
                    "data": "quantity_remaining",
                    "name": "quantity_remaining",
                    "class": "text-neutral-text-gray",
                    "visible": false
                },
                {
                    "title": "Picked at",
                    "data": "created_at",
                    "name": "tote_order_items.created_at",
                    "class": "text-neutral-text-gray",
                    "visible": false
                },
                {
                    "title": "Picked by",
                    "data": "picked_by",
                    "name": "tote_order_items.user_id",
                    "class": "text-neutral-text-gray",
                    "visible": false
                }
            ]
        });
    }

    $(document).ready(function () {
        dateTimePicker();
        dtDateRangePicker();
        $(document).find('select:not(.custom-select)').select2();

        $('.import-totes').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            let _form = $(this).closest('.import-totes-form');
            let form = _form[0];
            let formData = new FormData(form);

            $.ajax({
                type: 'POST',
                url: _form.attr('action'),
                headers: {'X-CSRF-TOKEN': formData.get('_token')},
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    $('#csv-filename').empty();

                    toastr.success(data.message);

                    window.dtInstances['#totes-table'].ajax.reload()
                },
                error: function (response) {
                    if (response.status !== 504) {
                        $('#csv-filename').empty();

                        toastr.error('Invalid CSV data');
                        appendValidationMessages(modal, response);
                    }
                }
            });

            $('#import-totes-modal').modal('hide');
            toastr.info('Totes import started. You may continue using the system');
        });

        $('.export-totes').click(function () {
            $('#export-totes-modal').modal('toggle');
        });

        $('#totes-csv-button').on('change', function (e) {
            if (e.target.files) {
                if (e.target.files[0]) {
                    let filename = e.target.files[0].name
                    $('#csv-filename').append(
                        '<h5 class="heading-small">' +
                        'Filename: ' + filename +
                        '</h5>'
                    )
                }

                $('#import-purchase-orders-modal').focus()
            }
        })
    })
}
