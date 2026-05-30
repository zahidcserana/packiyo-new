window.LocationForm = function (locationId = '') {
    $(document).find('select:not(.custom-select)').select2()
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    auditLog(locationId);

    if ($('#locations-table').length) {
        window.datatables.push({
            selector: '#locations-table',
            resource: 'locations',
            ajax: {
                url: '/locations/data-table',
                data: function (data) {
                    let request = window.serializeFilterForm(filterForm)

                    data.filter_form = request
                    data.from_date = $('#locations-table-date-filter').val()

                    window.queryUrl(request)

                    window.exportFilters['locations'] = data
                },
            },
            columns: [
                tables.bulkColumn(function (data) {
                    return `<button class="table-icon-button" type="button" data-id="${ data.id }" data-toggle="modal" data-target="#locationEditModal">
                            <i class="picon-edit-filled icon-lg" title="Edit"></i>
                        </button>`
                }),
                {
                    'title': 'Location',
                    'data': 'location_name',
                    'name': 'locations.name',
                },
                {
                    'title': 'Pickable',
                    'name': 'locations.pickable',
                    'data': function (data) {
                        return data.location_pickable
                    },
                },
                {
                    'title': 'Disabled on picking app',
                    'name': 'locations.disabled_on_picking_app',
                    'data': function (data) {
                        return data.location_disabled_on_picking_app
                    },
                },
                {
                    "title": "Bulk ship pickable",
                    "name": "locations.bulk_ship_pickable",
                    "data": function (data) {
                        return data.location_bulk_ship_pickable
                    },
                },
                {
                    'title': 'Sellable',
                    'name': 'locations.sellable',
                    'data': function (data) {
                        return data.location_sellable
                    },
                },
                {
                    "title": "Allow multiple lots",
                    "name": "locations.is_receiving",
                    "data": function (data) {
                        return data.location_receiving
                    },
                },
                {
                    "title": "Location type",
                    "name": "location_types.name",
                    "data": "location_types"
                },
                {
                    'title': 'Warehouse',
                    'name': 'contact_informations.name',
                    'data': function (data) {
                        return `<a href="${ data.warehouse_url }" target="_blank">${ data.warehouse_name }</a>`
                    },
                },
                {
                    'non_hiddable': true,
                    'orderable': false,
                    'class': 'text-right',
                    'title': '',
                    'data': function (data) {
                        let audit = `<a href="${ data.audit }" target="_blank"><i class="picon-show-light icon-lg" title="Audit"></i></a>&nbsp;`

                        return audit + app.tableDeleteButton(
                            `Are you sure you want to delete ${ data.location_name }?`,
                            data.link_delete
                        )
                    },
                },
            ],
            dropdownAutoWidth: true,
        })
    }

    $(document).ready(function () {
        $('#locationEditModal').on('show.bs.modal', function (e) {
            $('.locationForm').remove()

            $('#locationEditModal .modal-content').html(`<div class="spinner">
                <img src="../../img/loading.gif">
            </div>`)
            let locationId = $(e.relatedTarget).data('id')

            $.ajax({
                type: 'GET',
                serverSide: true,
                url: '/location/getLocationModal/' + locationId,
                success: function (data) {
                    $('#locationEditModal > div').html(data)
                },
                error: function (response) {
                    let modal = $('#locationEditModal')

                    appendValidationMessages(modal, response)
                },
            })
        })

        $('#locationCreateModal').on('show.bs.modal', function (e) {
            $('.locationForm').remove()

            $('#locationCreateModal .modal-content').html(`<div class="spinner">
                <img src="../../img/loading.gif">
            </div>`)

            $.ajax({
                type: 'GET',
                serverSide: true,
                url: '/location/getLocationModal',
                success: function (data) {
                    $('#locationCreateModal > div').html(data)
                },
                error: function (response) {
                    appendValidationMessages(
                        $('#locationCreateModal'),
                        response,
                    )
                },
            })
        })

        $('.import-locations').click(function (e) {
            e.preventDefault()
            e.stopPropagation()

            let _form = $(this).closest('.import-locations-form')
            let form = _form[0]
            let formData = new FormData(form)

            $.ajax({
                type: 'POST',
                url: _form.attr('action'),
                headers: {'X-CSRF-TOKEN': formData.get('_token')},
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    $('#csv-filename').empty()

                    toastr.success(data.message)

                    window.dtInstances['#locations-table'].ajax.reload()
                },
                error: function (response) {
                    if (response.status != 504) {
                        $('#csv-filename').empty()

                        toastr.error('Invalid CSV data')
                        appendValidationMessages(modal, response)
                    }
                },
            })

            $('#import-locations-modal').modal(`hide`)
            toastr.info('Location import started. You may continue using the system')
        })

        $('.export-locations').click(function () {
            $('#export-locations-modal').modal('toggle')
        })

        $('#locations-csv-button').on('change', function (e) {
            if (e.target.files) {
                if (e.target.files[0]) {
                    let filename = e.target.files[0].name
                    $('#csv-filename').append(
                        '<h5 class="heading-small">' +
                        'Filename: ' + filename +
                        '</h5>',
                    )
                }

                $('#import-locations-modal').focus()
            }
        })
    })
}
