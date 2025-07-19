window.BulkShipOrders = function (batchId) {
    let filterForm = null

    window.datatables.push({
        selector: '#bulk-ship-orders',
        resource: 'bulk-ship-orders',
        ajax: {
            url: `/packing/bulk_shipping/${batchId}/data-table`,
            data: function (data) {
                if (filterForm) {
                    data.filter_form = window.serializeFilterForm(filterForm)
                }
            }
        },
        createdRow: function(row, data) {
            $(row).attr('data-id', data['id'])

            $(row).find('td:eq(2)').attr('title', data['status_message'])
        },
        order: [0, 'desc'],
        columns: [
            {
                orderable: true,
                searchable: true,
                title: 'Order Number',
                name: 'order_number',
                data: function (data) {
                    return `
                        <a href="/order/edit/${data['id']}" target="_blank">
                            ${data['order_number']}
                        </a>
                    `
                }
            },
            {
                orderable: true,
                searchable: true,
                title: 'Shipping method',
                name: 'shipping_method',
                data: function (data) {
                    if (!data['shipment_id']) {
                        const dataId = data['id']
                        let shippingMethodSelect =  `<select
                                    name="shipping_method_id[${dataId}]"
                                    id="input-shipping_method_id[${dataId}]"
                                    class="set-shipping-method"
                                    data-order="${dataId}"
                                    >`

                        $.each(data['shipping_methods'], function(key, value) {
                            if (data['shipping_method_id'] && data['shipping_method_id'] == key) {
                                shippingMethodSelect += `<option value="${key}" selected>${value}</option>`
                            } else {
                                shippingMethodSelect += `<option value="${key}">${value}</option>`
                            }
                        })

                        shippingMethodSelect += `</select>`

                        return shippingMethodSelect
                    }

                    return data['shipping_method']
                }
            },
            {
                orderable: true,
                searchable: true,
                title: 'Status',
                name: 'status_message',
                data: function (data) {
                    if (data['shipment_id']) {
                        return 'Shipped'
                    }

                    return 'Not shipped'
                },
                className: 'bulk-ship-order-status'
            },
            {
                orderable: false,
                title: 'Action',
                name: 'action',
                data: function (data) {
                    return `
                    <button
                        type="button"
                        class="table-icon-button"
                        data-confirm-message="Are you sure you want to remove order ${data['order_number']} from the batch?"
                        data-confirm-button-text="Remove"
                        href="/packing/bulk_shipping/remove/${data['batch_id']}/${data['id']}"
                    >
                        <i class="picon-trash-filled del_icon icon-lg icon-orange" title="Remove"></i>
                    </button>

                    <i class="picon-edit-filled icon-lg icon-orange" data-target="#order-bulk-ship-shipping-information-edit" data-toggle="modal" data-order="${data['id']}"></i>
                    `
                }
            }
        ]
    })

    $('#apply-filter-button').on('click', function () {
        $('#filter-orders-in-batch-modal').modal('toggle')

        filterForm = $('#filter-orders-in-batch-form')

        window.loadFilterFromQuery(filterForm)

        window.dtInstances['#bulk-ship-orders'].ajax.reload()
    })

    $('.batch-filter-shipping-carrier').on('change', function () {
        $('#batch-filter-shipping-carrier').val($(this).val())
    })

    $('.batch-filter-shipping-method').on('change', function () {
        $('#batch-filter-shipping-method').val($(this).val())
    })
}
