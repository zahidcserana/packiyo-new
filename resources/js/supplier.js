window.Supplier = function () {
    $(document).ready(function () {
        $(document).find('select:not(.custom-select)').select2();
    });

    window.datatables.push({
        selector: '#supplier-table',
        resource: 'suppliers',
        ajax: {
            url: '/supplier/data-table',
            data: function (data) {
                window.exportFilters['suppliers'] = data
            }
        },
        columns: [
            {
                "orderable": false,
                "title": "",
                "class": "text-left",
                "data": function (data) {
                    return `
                        <button type="button" class="table-icon-button" data-id="${data.link_edit}" data-toggle="modal" data-target="#vendorEditModal">
                            <i class="picon-edit-filled icon-lg" title="Edit"></i>
                        </button>
                    `
                },
            },
            {
                "title": "Name",
                "data": "supplier_name",
                "name": "contact_informations.name"
            },
            {
                "title": "Address",
                "data": "supplier_address",
                "name": "contact_informations.address"
            },
            {
                "title": "Zip", "data":
                    "supplier_zip",
                "name": "contact_informations.zip"
            },
            {
                "title": "City",
                "data": "supplier_city",
                "name": "contact_informations.city"
            },
            {
                "title": "Email",
                "data": "supplier_email",
                "name": "contact_informations.email"
            },
            {
                "title": "Phone",
                "data": "supplier_phone",
                "name": "contact_informations.phone"
            },
            {
                "title": "Customer",
                "name": "customer_contact_information.name",
                "data": function (data) {
                    return data.customer['name']
                }
            }
        ],
        dropdownAutoWidth : true,
    })

    $(document).ready(function() {
        $(document).find('select:not(.custom-select)').select2();

        function openCreationModal() {
            let hash = window.location.hash;

            if (hash && hash === '#open-modal') {
                $(document).find('#supplierCreateModal').modal('show')

                $('#supplierCreateModal select').each((i, element) => {
                    if ($(element).hasClass('select2-hidden-accessible')) {
                        $(element).select2('destroy');
                    }

                    $(element).select2({
                        dropdownParent: $("#supplierCreateModal")
                    })
                });

                window.location.hash = '';
            }
        }

        function openEditModal() {
            let hash = window.location.hash;

            if (!hash) {
                return
            }

            let editModal = hash.split('-');

            if (editModal.length > 0 && editModal[0] === '#editModal') {
                $('button[data-id="' + editModal[1] + '"]').trigger('click');

                window.location.hash = '';
            }
        }

        openCreationModal();

        setTimeout(openEditModal, 2000);

        $(document).on('click', '.openPurchaseOrderCreateModal', function () {
            openCreationModal();
        })

        $(window).on('hashchange', function (e) {
            openCreationModal();
        });

        $('.modal-create-submit-button').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            $(document).find('span.invalid-feedback').remove()

            let _form = $(this).closest('.supplierForm');
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
                    form.reset()

                    $('#supplierCreateModal').modal('toggle');

                    toastr.success(data.message)

                    window.dtInstances['#supplier-table'].ajax.reload()
                },
                error: function (response) {
                    appendValidationMessages($('#supplierCreateModal'), response)
                }
            });
        });

        $('#supplierCreateModal').on('show.bs.modal', function (e) {
            $('#supplierCreateModal select').each((i, element) => {
                if ($(element).hasClass('select2-hidden-accessible')) {
                    $(element).select2('destroy');
                }

                $(element).select2({
                    dropdownParent: $("#supplierCreateModal")
                })
            });
        });

        $('#vendorEditModal').on('show.bs.modal', function (e) {
            $('#vendorEditModal .modal-content').html(`<div class="spinner">
                <img src="../../img/loading.gif">
            </div>`)
            let itemId = $(e.relatedTarget).data('id');

            $.ajax({
                type:'GET',
                serverSide: true,
                url:'/supplier/getVendorModal/' + itemId,

                success: function(data) {
                    $('#vendorEditModal > div').html(data);

                    $('#vendorEditModal select').each((i, element) => {
                        if ($(element).hasClass('select2-hidden-accessible')) {
                            $(element).select2('destroy');
                        }

                        $(element).select2({
                            dropdownParent: $("#vendorEditModal")
                        })
                    });
                },
                error: function (response) {
                    let modal = $('#vendorEditModal');

                    appendValidationMessages(modal, response)
                }
            });
        })

        $('.deleteVendor').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            let _form = $(this).closest('#deleteVendorForm');
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
                    $('#vendorDeleteModal').modal('toggle');
                    $('#vendorEditModal').modal('toggle');

                    toastr.success(data.message)

                    window.dtInstances['#supplier-table'].ajax.reload()
                }
            });
        });
    });

    $(document).ready(function() {
        $('.import-vendors').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            let _form = $(this).closest('.import-vendors-form');
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
                    $('#csv-filename').empty()

                    toastr.success(data.message)

                    window.dtInstances['#supplier-table'].ajax.reload()
                },
                error: function (response) {
                    if (response.status != 504) {
                        if (response.status === 403) {
                            toastr.error('Action is not permitted for this customer')
                        } else {
                            let modal = $('#import-vendors-modal');
                            toastr.error('Invalid CSV data')

                            $('#csv-filename').empty()

                            appendValidationMessages(modal, response);
                        }
                    }
                }
            });

            $('#import-vendors-modal').modal('hide');
            toastr.info('Vendor import started. You may continue using the system');
        });

        $('#vendors-csv-button').on('change', function (e) {
           if (e.target.files) {
               if (e.target.files[0]) {
                   let filename = e.target.files[0].name
                   $('#csv-filename').append(
                       '<h5 class="heading-small">' +
                       'Filename: ' + filename +
                       '</h5>'
                   )
               }

               $('#import-vendors-modal').focus()
           }
        })

        $('.export-suppliers').click(function () {
            $('#export-suppliers-modal').modal('toggle');
        });
    });
};
