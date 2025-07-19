window.Lot = function () {
    window.datatables.push({
        selector: '#lot-table',
        resource: 'lot',
        ajax: {
            url: '/lot/data-table'
        },
        columns: [
            {
                "non_hiddable":true,
                "orderable": false,
                "class": "text-left",
                "title": "",
                "name":"id",
                "data": function (data) {
                    let editButton = `
                        <button type="button" class="d-flex table-icon-button" href="${data.link_edit}" data-id="${data.id}" data-toggle="modal" data-target="#create-edit-modal">
                            <i class="picon-edit-filled icon-lg" title="Edit"></i>
                        </button>`;

                    return editButton
                },
            },
            {
                "title": "Name",
                "data": "name",
                "name": "lots.name",
            },
            {
                "title": "Customer",
                "data": function (data) {
                    return '<a href="'+ data.customer.url +'" class="text-neutral-text-gray">' + data.customer.name + '</a>';
                },
                "name": "customer_contact_information.name",
                "class": "text-neutral-text-gray"
            },
            {
                "title": "SKU",
                "data": "product.sku",
                "name": "products.sku",
                "class": "text-neutral-text-gray"
            },
            {
                "title": "Product",
                "data": function (data) {
                    return '<a href="'+ data.product.url +'" class="text-neutral-text-gray">' + data.product.name + '</a>';
                },
                "name": "products.name",
                "class": "text-neutral-text-gray"
            },
            {
                "title": "Item price",
                "data": "item_price",
                "name": "item_price",
            },
            {
                "title": "Expiration Date",
                "data": function (data) {
                    return data.expiration_date;
                },
                "name": "expiration_date",
                "class": "text-neutral-text-gray"
            },
            {
                "title": "Created at",
                "data": "created_at",
                "name": "created_at",
                "visible": false
            },
            {
                'non_hiddable': true,
                "orderable": false,
                "class":"text-right",
                "title": "",
                "name":"action",
                "data": function (data) {
                    return app.tableDeleteButton(
                        `Are you sure you want to delete ${data.name}?`,
                        data.link_delete
                    );
                }
            }
        ],
        dropdownAutoWidth : true,
    })

    $(document).ready(function() {
        $(document).on('show.bs.modal', '#create-edit-modal', function (e) {
            let modal = $(this)
            let button = $(e.relatedTarget)

            $.ajax({
                url: button.attr('href'),
                success: function (data) {
                    modal.find('div').html(data)

                    modal.find('.ajax-user-input').select2({
                        dropdownParent: modal
                    })

                    runLotFunctions()
                }
            })
        })

        $(document).on('submit', '#create-edit-form', function (e) {
            e.preventDefault()

            let url = $(this).attr('action')
            let data = $(this).serialize()

            $.ajax({
                type: "POST",
                url: url,
                data: data,
                success: function (response) {
                    window.dtInstances['#lot-table'].ajax.reload()

                    toastr.success(response.message)

                    $('#create-edit-modal').modal('hide')
                },
                error: function (response) {
                    appendValidationMessages($('#create-edit-modal'), response)
                }
            });
        });
    });

    function runLotFunctions(){
        let customersSelect = $('#lot_customer_id')
        let suppliersSelect = $('#lot_supplier_id')
        let productsSelect = $('#lot_product_id')

        let suppliersBaseAjaxUrl = $('select[name="supplier_id"]').data('ajax--url')
        let productsBaseAjaxUrl = $('select[name="product_id"]').data('ajax--url')

        $(document).ready(function() {
            if (customersSelect.val() > 0) {
                $('#customers_container').hide()

                switchSelect2AjaxURL(suppliersSelect, suppliersBaseAjaxUrl + '/' + customersSelect.val())

                $('#suppliers_container').show()
            } else {
                $('#suppliers_container').hide()
            }
        })

        customersSelect.on('change', function(){
            switchSelect2AjaxURL(suppliersSelect, suppliersBaseAjaxUrl + '/' + $(this).val())

            $('#suppliers_container').show()
        })

        suppliersSelect.on('change', function(){
            let selectedSupplierId = $(this).val()

            switchSelect2AjaxURL(productsSelect, productsBaseAjaxUrl + '/' + selectedSupplierId + '/?lot')

            $('#products_container').show()
        })
    }

    function switchSelect2AjaxURL(select, url) {
        select.select2('destroy')
        select.data('ajax--url', url)
        select.select2({
            dropdownParent: $("#create-edit-modal")
        })
    }
}
