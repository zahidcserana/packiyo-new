window.Kit = function () {
    const filterForm = $('#toggleFilterForm').find('form')

    window.datatables.push({
        selector: '#kits-table',
        resource: 'kits',
        ajax: {
            url: '/kits/data-table',
            data: function (data) {
                let request = {}
                filterForm
                    .serializeArray()
                    .map(function(input) {
                        request[input.name] = input.value;
                    });

                data.filter_form = request

                window.exportFilters['kits'] = request
            }
        },
        columns: [
            {
                "orderable": false,
                "class": "text-left",
                "createdCell": (cell) => {
                    $(cell).addClass("d-flex pt-4")
                },
                "title": `<div class="custom-datatable-checkbox-container-header">
                                <div>
                                    <input id="select-all-checkboxes" type="checkbox" value="0">
                                    <label for="select-all-checkboxes"></label>
                                </div>
                              </div>`,
                "name": "products.id",
                "data": function (data) {
                    let editButton = `
                            <a type="button" class="table-icon-button" href="/product/${data.id}/edit">
                                <i class="picon-edit-filled icon-lg" title="Edit"></i>
                            </a>
                        `;

                    let bulkEditCheckbox = `
                            <div class="custom-datatable-checkbox-container">
                                <div>
                                    <input name="bulk-edit[${data.id}]" id="bulk-edit-${data.id}" class="custom-datatable-checkbox" type="checkbox" value="0">
                                    <label class="mb-0" for="bulk-edit-${data.id}"></label>
                                </div>
                            </div>
                        `;

                    return bulkEditCheckbox + editButton;
                },
            },
            {
                "title": "Image",
                "name": "image",
                "orderable": false,
                "searchable": false,
                "data": function (data) {
                    return `
                            <a href="#" title="{{ __('Show image') }}" data-toggle="modal" data-target="#big-image-modal" data-image="${data.image}">
                                <img src="${data.image}" class="img-thumbnail" />
                            </a>
                        `
                }
            },
            {
                "title": "Name",
                "data": function (data) {
                    return `<a href="${data.link_edit}">${data.name}</a>`;
                },
                "name": "products.name",
                'visible': false
            },
            {
                "title": "On Hand",
                "data": "quantity_on_hand",
                "name": "products.quantity_on_hand",
                'visible': false
            },
            {
                "title": "SKU",
                "data": function (data) {
                    return `<a href="${data.link_edit}">${data.sku}</a>`;
                },
                "name": "products.sku",
                'visible': false

            },
            {
                "title": "Available",
                "data": "quantity_available",
                "name": "products.quantity_available",
                'visible': false

            },
            {
                "title": "Price",
                "data": "price",
                "name": "products.price",
                'visible': false

            },
            {
                "title": "Allocated",
                "data": "quantity_allocated",
                "name": "products.quantity_allocated",
                'visible': false
            },
            {
                "title": "Backorder",
                "data": "quantity_backordered",
                "name": "products.quantity_backordered",
                'visible': false
            },
            {
                "title": "Barcode",
                "name": "products.barcode",
                'visible': false,
                "data": function (data) {
                    return `
                            <div class="d-flex align-items-center">
                                <span class="mr-2">${data.barcode}</span>
                                <div
                                    data-target="#chooseHowMuchToPrint"
                                    data-toggle="modal"
                                    class="d-flex align-items-center"
                                    data-customer-printers-url="${data.customer_printers_url}"
                                    data-barcode-pdf-url="${data.barcode_pdf_url}"
                                    data-submit-action="${data.print_barcodes_url}"
                                >
                                    <button class="table-icon-button">
                                        <i class="pr-2 picon-printer-light icon-lg align-middle"></i>
                                    </button>
                                </div>
                            </div>
                        `
                },
            },
            {
                "title": "Date",
                "data": "date",
                "name": "products.created_at",
                'visible': false
            },
            {
                'non_hiddable': true,
                "orderable": false,
                "class": "text-right",
                "title": "",
                "name": "",
                "data": function (data) {
                    let recoverButton = `<button title="Recover product" type="button" data-id="${data.id}" class="delete-icon recover-icon table-icon-button" data-toggle="modal" data-target="#recover-product-modal">
                                <i class="picon-history-filled icon-lg" title="Recover"></i>
                            </button>`;

                    let deleteButton = app.tableDeleteButton(
                        `<strong>Are you sure you want to delete this product?</strong><br /><br />
                            <strong>Name: </strong>${data.name}<br />
                            <strong>SKU: </strong>${data.sku}`,
                        data.link_delete,
                        true
                    );

                    if (data.is_deleted) {
                        return recoverButton;
                    } else {
                        return deleteButton;
                    }
                }
            }
        ],
        dropdownAutoWidth : true,
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td.quantity_editable')
                .attr("data-product-id", data.product_id)
                .attr("data-location-id", data.location_id)
                .attr("data-location-product-id", data.location_product_id)
                .attr("data-quantity-on-hand", data.quantity)
                .attr('title', 'Edit quantity')
                .css('min-width', '100px');
        }
    })

    $(document).ready(function() {
        $(document).find('select:not(.custom-select)').select2();

        $('.import-kits').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            let _form = $(this).closest('.import-kits-form');
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
                    $('#kit-csv-filename').empty();

                    toastr.success(data.message)

                    window.dtInstances['#kits-table'].ajax.reload()
                },
                error: function (response) {
                    if (response.status !== 504) {
                        $('#kit-csv-filename').empty();

                        if (response.status === 403) {
                            toastr.error('Action is not permitted for this customer')
                        } else {
                            toastr.error('Invalid CSV data')
                            appendValidationMessages($('#import-kits-modal'), response)
                        }
                    }
                }
            });

            $('.import-kits-form')[0].reset()
            $('#import-kits-modal').modal('hide');
            toastr.info('Kits import started. You may continue using the system');
        });

        })
        $('.export-inventory').click(function () {
            $('#export-kits-modal').modal('toggle')
        });

        $('#kits-csv-button').on('change', function (e) {
           if (e.target.files) {
               if (e.target.files[0]) {
                   let filename = e.target.files[0].name
                   $('#kit-csv-filename').append(
                       '<h5 class="heading-small">' +
                       'Filename: ' + filename +
                       '</h5>'
                   )
               }

               $('#import-kits-modal').focus()
           }
        })
};
