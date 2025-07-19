window.ShippedItemReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const selector = '#shipped_item-table';

    window.datatables.push({
        selector: selector,
        resource: 'shipped_item',
        ajax: {
            url: '/report/shipped_item/data_table/',
            data: function(data){
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['shipped_item'] = data
            }
        },
        order: [11, 'desc'],
        columns: [
            {
                'title': 'SKU',
                'data': function (data) {
                    return `<a href="${data.product.url}">${data.product.sku}</a>`;
                },
                'name': 'products.sku'
            },
            {
                'title': 'Product name',
                'data': function (data) {
                    return `<a href="${data.product.url}">${data.product.name}</a>`;
                },
                'name': 'products.name',
                'visible': false
            },
            {
                'title': 'Order number',
                'data': function (data) {
                    return `
                        <a href="${data.order.url}">${data.order.number}</a>
                    `
                },
                'name': 'orders.number',
            },
            {
                "title": "Customer",
                "data": function (data) {
                    return data.customer['name']
                },
                "name": "customer_contact_information.name",
                "visible": false,
                orderable: false,
            },
            {
                'hidden_when_load': true,
                'title': 'QTY ordered',
                'data': 'qty_ordered',
                'name' : 'order_items.quantity',
            },
            {
                'hidden_when_load': true,
                'title': 'QTY shipped',
                'data': 'qty_shipped',
                'name' : 'package_order_items.quantity',
            },
            {
                'hidden_when_load': true,
                'title': 'Price',
                'data': 'price',
                'name': 'order_items.price',
                'visible': false
            },
            {
                'hidden_when_load': true,
                'orderable': false,
                'title': 'Price total',
                'data': 'price_total',
                'name': 'price_total',
                'visible': false
            },
            {
                'hidden_when_load': true,
                'title': 'Store',
                'data': 'store',
                'name' : 'order_channels.name',
            },
            {
                'hidden_when_load': true,
                'title': 'Location',
                'data': 'location_name',
                'name': 'locations.name',
                'visible': false
            },
            {
                'hidden_when_load': true,
                'title': 'Shipping method',
                'data': 'shipping_method',
                'name' : 'shipping_methods.name',
            },
            {
                'hidden_when_load': true,
                'title': 'Carrier',
                'data': 'shipping_carrier',
                'name' : 'shipping_carriers.name',
            },
            {
                'hidden_when_load': true,
                'title': 'Order time',
                'data': 'order.ordered_at',
                'name': 'orders.ordered_at',
                'visible': false
            },
            {
                'hidden_when_load': true,
                'title': 'Date shipped',
                'data': 'date_shipped',
                'name': 'shipments.created_at',
                'visible': false
            },
            {
                'hidden_when_load': true,
                'title': 'Packer',
                'data': 'packer',
                'name': 'contact_informations.name',
                'visible': false
            },
            {
                'title': 'Tote',
                'name': 'totes.name',
                'data': function (data) {
                    if (data.tote) {
                        return `
                            <a href="${data.tote.url}">${data.tote.name}</a>
                        `
                    }
                },
            },
            {
                'title': 'Lot',
                'name': 'lots.name',
                'data': 'lot.name',
            },
            {
                'hidden_when_load': true,
                'title': 'Lot expiration',
                'data': 'lot.expiration_date',
                'name': 'lots.expiration_date',
                'visible': false
            },
            {
                'title': 'Serial number',
                'name': 'serial_number',
                'data': 'serial_number',
            },
            {
                'title': 'Tracking #',
                'data': 'tracking_number',
                'name': 'tracking_number',
            },
            {
                'title': 'Shipping box',
                'name': 'shipping_boxes.name',
                'data': function (data) {
                    if (data.shippingBox) {
                        return `
                            <a href="${data.shippingBox.url}">${data.shippingBox.name}</a>
                        `
                    }
                },
            }
        ]
    })

    $(document).ready(function() {
        dateTimePicker();
        dtDateRangePicker();
        $(document).find('select:not(.custom-select)').select2();
    })
}
