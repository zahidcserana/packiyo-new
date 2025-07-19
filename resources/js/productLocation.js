window.ProductLocation = function () {
    $(document).find('select:not(.custom-select)').select2();
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)

    const makeQuatityCell = function (data) {
        let icon = `<i class="edit-quantity picon-edit-filled icon-lg ml-sm-auto cursor-pointer" title="Edit"></i>`;

        if (window.app.data.is_3pl_child) {
            icon = '';
        }

        return `
                <div class="d-flex">
                    <span>${data.quantity}</span> ${icon}
                </div>
            `;
    }

    window.datatables.push({
        selector: '#product-location-table',
        resource: 'location_product',
        ajax: {
            url: '/locations/product/data-table',
            data: function (data) {
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['location_product'] = data
            }
        },
        columns: [
            tables.bulkColumn(function () {
                return ``
            }),
            {
                "title": "",
                "data": "id",
                "name": "locations.id",
                "visible": false
            },
            {
                "title": "Location",
                "data": "location",
                "name": "locations.name"
            },
            {
                "orderable": false,
                "title": "Warehouse",
                "data": "warehouse",
                "name": "warehouse"
            },
            {
                "title": "SKU",
                "data": "sku",
                "name": "products.sku"
            },
            {
                "title": "Product name",
                "data": "product_name",
                "name": "products.name"
            },
            {
                "title": "Lot name",
                "data": "lot_name",
                "name": "lots.name"
            },
            {
                "title": "Lot expiration date",
                "data": "lot_expiration_date",
                "name": "lots.expiration_date",
            },
            {
                "title": "Quantity",
                "name": "location_product.quantity_on_hand",
                "className": "quantity_editable",
                "data": function (data) {
                    return makeQuatityCell(data);
                },
                "width": "20%"
            },
            {
                "title": "Pickable",
                "name": "locations.pickable",
                "data": function (data) {
                    return data.location_pickable;
                },
            },
            {
                "title": "Sellable",
                "name": "locations.sellable",
                "data": function (data) {
                    return data.location_sellable;
                },
            }
        ],
        dropdownAutoWidth : true,
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td.quantity_editable')
                    .attr("data-product-id", data.product_id)
                    .attr("data-location-id", data.location_id)
                    .attr("data-location-product-id", data.location_product_id)
                    .attr("data-quantity-on-hand", data.quantity)
                    .attr('data-lot-id', data.lot_id)
                    .attr('title', 'Edit quantity')
                    .css('min-width', '100px')
        }
    })

    $(document).ready(function() {
        $('.warehouse_id').select2({
            dropdownParent: $("#import-inventory-modal")
        });

        $('.importInventory').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            let _form = $(this).closest('.importInventoryForm');
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

                    window.location.reload()
                },
                error: function (response) {
                    if (response.status != 504) {
                        if (response.status === 403) {
                            toastr.error('Action is not permitted for this customer')
                        } else {
                            toastr.error('Invalid CSV data')

                            $('#csv-filename').empty()

                            appendValidationMessages(modal, response);
                        }
                    }
                }
            });

            $('#import-inventory-modal').modal('hide');
            toastr.info('Inventory import started. You may continue using the system');
        });

        $('#InventoryCsvButton').on('change', function (e) {
           if (e.target.files) {
               if (e.target.files[0]) {
                   let filename = e.target.files[0].name
                   $('#csv-filename').append(
                       '<h5 class="heading-small">' +
                       'Filename: ' + filename +
                       '</h5>'
                   )
               }

               $('#import-inventory-modal').focus()
           }
        })

        const putPreviousValue = function ($td) {
            drawQuantityCell($td, $td.data("quantity-on-hand"));
        };

        const storeNewValue = function (input) {
            const $this = $(input);

            const val = $this.val();
            const $td = $this.closest("td");

            if (Number(val) === Number($td.data("quantity-on-hand")) || $this.hasClass('disabled')) {
                return;
            }

            $this.addClass('disabled');

            const locationId = $td.data("location-id");
            const productId = $td.data("product-id");
            const locationProductId = $td.data("location-product-id");
            const lotId = $td.data("lot-id");

            $.ajax({
                url:
                    `/location/${locationId}/product/${productId}/quantity`,
                type: "patch",
                data: {
                    quantity_on_hand: val,
                    lot_id: lotId,
                    location_product_id: locationProductId,
                },
                dataType: "json",
                success: function (data) {
                    drawQuantityCell($td, val);
                    toastr.success(data.message);
                },
                error: function (messages) {
                    $this.removeClass('disabled');
                    if (messages.responseJSON.errors) {
                        $.each(
                            messages.responseJSON.errors,
                            function (key, value) {
                                toastr.error(value);
                            }
                        );
                    }
                },
            });
        };

        const drawQuantityCell = function ($td, quantity) {
            $td.empty().html(makeQuatityCell({quantity: quantity}));
            $td.data("quantity-on-hand", quantity);
            $td.removeClass("show");
        }

        $(document).on("click", "tr td .edit-quantity", function (e) {
            $this = $(this).closest('td');

            if ($this.hasClass("show")) {
                return;
            }

            if (! $this.hasClass("show")) {
                $this.addClass("show");
            }

            const saveIcon = '<i class="save-icon picon-check-circled-light icon-lg" title="Confirm"></i>';
            const cancelIcon = '<i class="cancel-icon picon-close-light icon-lg cursor-pointer text-red" title="Cancel"></i>';
            const input = `
                <div class="d-flex align-items-center">
                    <div class="input-group input-group-alternative input-group-merge mr-2">
                        <input type="number" class="form-control locationQuantity form-control font-weight-600 text-black h-auto editfield">
                    </div>
                    <div class="ml-sm-auto">
                        ${saveIcon} ${cancelIcon}
                    </div>
                </div>
            `

            const val = $this.data("quantity-on-hand");
            $this.empty();
            $(input)
                .appendTo($this)
                .find("input")
                .val(val)
                .focus()
                .on("change", function () {
                    $this = $(this);
                    const $confirmIcon = $this.closest('td').find('.save-icon');
                    const $td = $this.closest("td");
                    const val = $this.val();

                    if (Number(val) === Number($td.data("quantity-on-hand"))) {
                        if ($confirmIcon.hasClass('text-green')) {
                            $confirmIcon.removeClass('text-green');
                            $confirmIcon.removeClass('cursor-pointer');
                        }
                    } else {
                        $confirmIcon.addClass('text-green');
                        $confirmIcon.addClass('cursor-pointer');
                    }
                });
        });

        $(document).on("click", "tr td.quantity_editable.show .cancel-icon", function (e) {
            putPreviousValue($(this).closest('td'));
        });

        $(document).on("click", "tr td.quantity_editable.show .save-icon", function (e) {
            storeNewValue($(this).closest('td').find('input.editfield'));
        });

        $('.export-inventory').click(function () {
            $('#export-inventory-modal').modal('toggle')
        });

        let customerSelect = $('.customer_id');
        let warehouseSelect = $('.enabled-for-customer[name="warehouse_id"]');

        function toggleInputs() {
            if (!customerSelect.val()) {
                warehouseSelect.prop('disabled', true);
                warehouseSelect.append(new Option('Select', 'title', true, false));

                if (warehouseSelect.left > 0) {
                    warehouseSelect[0].options[0].disabled = true;
                }
            } else {
                warehouseSelect.prop('disabled', false);
            }
        }

        customerSelect.on('change', function () {
            let customerId = customerSelect.val();
            let selectedWarehouse = warehouseSelect.val();

            warehouseSelect.empty();

            toggleInputs();

            if (customerId) {
                $.get('/purchase_orders/filterWarehouses/' + customerId, function(data) {
                    $.map(data.results, function(result) {
                        if (!warehouseSelect.find(`option[value="${result.id}"]`).length) {
                            let selected = Number(result.id) == Number(selectedWarehouse);
                            warehouseSelect.append(new Option(result.text, result.id, selected, selected));
                        }
                    })
                });
            }
        }).trigger('change');
    });

    $('.delete-empty-locations-button').on('click', function () {
        $.ajax({
            type: 'GET',
            serverSide: true,
            url: '/locations/empty',
            success: function (data) {
                let emptyCount = data.data

                if (emptyCount > 0) {
                    app.confirm(null, `${emptyCount} empty product locations will be removed. Do you wish to continue?`, () => {
                        $.ajax({
                            type: 'DELETE',
                            serverSide: true,
                            url: '/locations/empty',
                            success: function (data) {
                                if (data.success) {
                                    toastr.success(data.message);
                                } else {
                                    toastr.warning(data.message);
                                }
                            },
                            error: function () {
                                toastr.warning('Something went wrong!')
                            }
                        });
                    });
                } else {
                    toastr.warning('No empty location found!')
                }
            }
        })
    });
};
