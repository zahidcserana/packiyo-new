window.ShippingBox = function () {
    $(document).find('select:not(.custom-select)').select2();
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm);

    window.datatables.push({
        selector: '#shipping-box-table',
        resource: 'shipping-box',
        ajax: {
            url: '/shipping_box/data-table',
            data: function (data) {
                data.filter_form = window.serializeFilterForm(filterForm)
                window.exportFilters['shipping-box'] = data
            }
        },
        order: [1, 'desc'],
        columns: [
            {
                "non_hiddable": true,
                "orderable": false,
                "class": "text-left",
                "title": "",
                "name": "edit",
                "data": function (data) {
                    return `<a type="button" class="table-icon-button" data-id="${data.id}" href="${data.link_edit}">
                        <i class="picon-edit-filled icon-lg" title="Edit"></i>
                    </a>`;
                },
            },
            {
                "title": "Name",
                "data": "name",
                "name": "name",
            },
            {
                "title": "Type",
                "data": "type",
                "name": "type",
            },
            {
                "title": "Weight",
                "data": "weight",
                "name": "weight",
            },
            {
                "title": "Length",
                "data": "length",
                "name": "length",
            },
            {
                "title": "Width",
                "data": "width",
                "name": "width",
            },
            {
                "title": "Height",
                "data": "height",
                "name": "height",
            },
            {
                "title": "Cost",
                "data": "cost",
                "name": "cost",
            },
            {
                "title": "Customer",
                "data": function (data) {
                    return '<a href="' + data.customer.url + '" class="text-neutral-text-gray">' + data.customer.name + '</a>';
                },
                "name": "customer.name",
                "class": "text-neutral-text-gray"
            },
            {
                'non_hiddable': true,
                "orderable": false,
                "class": "text-right",
                "title": "",
                "name": "delete",
                "data": function (data) {
                    return app.tableDeleteButton(
                        `Are you sure you want to delete ${data.name}?`,
                        data.link_delete
                    );
                }
            },
        ]
    })

    $(document).ready(function () {
        $('.import-shipping-box').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            let _form = $(this).closest('.import-shipping-box-form');
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
                    window.dtInstances['#shipping-box-table'].ajax.reload();
                },
                error: function (response) {
                    if (response.status != 504) {
                        $('#csv-filename').empty();
                        toastr.error('Invalid CSV data');

                        if (typeof response.responseJSON !== 'undefined') {
                            appendValidationMessages($('#import-shipping-box'), response);
                        }
                    }
                }
            });

            $('#import-shipping-box-modal').modal('hide');
            toastr.info('Shipping Box import started. You may continue using the system');
        });

        $('.export-shipping-box').click(function () {
            $('#export-shipping-box-modal').modal('toggle');
        });

        $('#shipping-box-csv-button').on('change', function (e) {
            if (e.target.files) {
                if (e.target.files[0]) {
                    let filename = e.target.files[0].name
                    $('#csv-filename').append(
                        '<h5 class="heading-small">' +
                        'Filename: ' + filename +
                        '</h5>'
                    )
                }

                $('#import-shipping-box-modal').focus()
            }
        })
    });
};
