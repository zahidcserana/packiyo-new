window.LocationType = (locationType = '') => {
    $(document).find('select:not(.custom-select)').select2();
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm);
    auditLog(locationType);

    window.datatables.push({
        selector: '#location-type-table',
        resource: 'location-type',
        ajax: {
            url: '/location/types/data-table',
            data: function (data) {
                window.exportFilters['location-type'] = data
            }
        },
        order: [1, 'desc'],
        columns: [
            tables.bulkColumn(function (data) {
                return `<button class="table-icon-button" type="button" onclick="window.location.href='${data.link_edit}'" data-id="${data.id}">
                            <i class="picon-edit-filled icon-lg" title="Edit"></i>
                        </button>`
            }),
            {
                "title": "Name",
                "data": "name",
                "name": "name",
            },
            {
                "title": "Pickable",
                "data": "pickable",
                "name": "pickable",
            },
            {
                "title": "Bulk ship pickable",
                "data": "bulk_ship_pickable",
                "name": "bulk_ship_pickable",
            },
            {
                "title": "Sellable",
                "data": "sellable",
                "name": "sellable",
            },
            {
                "title": "Disabled on picking app",
                "data": "disabled_on_picking_app",
                "name": "disabled_on_picking_app",
            },
            {
                "title": "Customer",
                "data": function (data) {
                    return '<a href="' + data.customer.url + '" class="text-neutral-text-gray">' + data.customer.name + '</a>'
                },
                "name": "customer_contact_information.name",
                "class": "text-neutral-text-gray"
            },
            {
                "non_hiddable": true,
                "orderable": false,
                "class": "text-right",
                "title": "",
                "name": "action",
                "data": function (data) {
                    return app.tableDeleteButton(
                        `Are you sure you want to delete ${data.name}?`,
                        data.link_delete
                    );
                },
            }
        ]
    })

    $(document).ready(function () {
        $('.import-location-type').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            let _form = $(this).closest('.import-location-type-form');
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
                    window.dtInstances['#location-type-table'].ajax.reload();
                },
                error: function (response) {
                    if (response.status != 504) {
                        $('#csv-filename').empty();
                        toastr.error('Invalid CSV data');

                        if (typeof response.responseJSON !== 'undefined') {
                            appendValidationMessages($('#import-location-type'), response);
                        }
                    }
                }
            });

            $('#import-location-type-modal').modal('hide');
            toastr.info('Location Type import started. You may continue using the system');
        });

        $('.export-location-type').click(function () {
            $('#export-location-type-modal').modal('toggle');
        });

        $('#location-type-csv-button').on('change', function (e) {
            if (e.target.files) {
                if (e.target.files[0]) {
                    let filename = e.target.files[0].name
                    $('#csv-filename').append(
                        '<h5 class="heading-small">' +
                        'Filename: ' + filename +
                        '</h5>'
                    )
                }

                $('#import-location-type-modal').focus()
            }
        })
    });
}
