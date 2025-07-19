window.ShipmentReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const selector = '#shipment-table';

    window.datatables.push({
        selector: selector,
        resource: 'shipment',
        ajax: {
            url:'/report/shipment/data_table',
            data: function(data){
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['shipment'] = data
            }
        },
        order: [1, 'desc'],
        columns: [
            {
                "title": "Order Number",
                "name": "orders.number",
                "data": function (data) {
                    let tooltipTitle = '';
                    data.order_products.map(function(orderProduct) {
                        tooltipTitle += orderProduct.quantity + ' - ' + orderProduct.sku + ' (' + orderProduct.name + ')<br/>';
                    });

                    return `
                        <span class="text-dark d-flex align-items-center">
                            <i class="picon-alert-circled-light mr-1" data-toggle="tooltip" data-placement="top" data-html="true" title="${escapeQuotes(tooltipTitle)}"></i>
                            <a href="/order/${data.order['id']}/edit" data-id="${data.order['id']}" target="_blank">
                                ${data.order['number']}
                            </a>
                        </span>
                    `
                },
            },
            {
                'title': 'Shipment Date',
                'name': 'shipments.created_at',
                'data': 'shipment_date'
            },
            {
                'hidden_when_load': true,
                'title': 'Order Date',
                'name': 'orders.ordered_at',
                'data': 'order_date'
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
                'title': 'Labels',
                'name': 'shipment_labels.size',
                'data': 'shipment_labels',
                'orderable': false
            },
            {
                'hidden_when_load': true,
                'title': 'Number',
                'data': 'tracking_number',
                'name': 'shipment_trackings.tracking_number',
                'orderable': false
            },
            {
                'title': 'Package',
                'data': 'shipping_box',
                'name': 'shipping_box',
                'orderable': false,
            },
            {
                'title': 'Carrier',
                'data': 'shipping_carrier',
                'name': 'shipping_carriers.name'
            },
            {
                'title': 'Method',
                'data': 'shipping_method',
                'name': 'shipping_methods.name'
            },
            {
                'hidden_when_load': true,
                'title': 'Name',
                'data': 'order_shipping_name',
                'name': 'shipping_contact_information.name'
            },
            {
                'title': 'Address',
                'data': 'order_shipping_address',
                'name': 'shipping_contact_information.address'
            },
            {
                'title': 'Address2',
                'data': 'order_shipping_address2',
                'name': 'shipping_contact_information.address2'
            },
            {
                'title': 'City',
                'data': 'order_shipping_city',
                'name': 'shipping_contact_information.city'
            },
            {
                'title': 'State',
                'data': 'order_shipping_state',
                'name': 'shipping_contact_information.state'
            },
            {
                'title': 'ZIP',
                'data': 'order_shipping_zip',
                'name': 'shipping_contact_information.zip'
            },
            {
                'title': 'Country',
                'data': 'order_shipping_country',
                'name': 'shipping_contact_information.name'
            },
            {
                'title': 'Company',
                'data': 'order_shipping_company',
                'name': 'shipping_contact_information.company_name'
            },
            {
                'title': 'Phone',
                'data': 'order_shipping_phone',
                'name': 'shipping_contact_information.phone'
            },
            {
                'title': 'Shipping Email',
                'data': 'order_shipping_email',
                'name': 'shipping_contact_information.email'
            },
            {
                'orderable': false,
                'title': 'Total Pieces',
                'data': 'line_item_total',
                'name': 'line_item_total'
            },
            {
                'orderable': false,
                'title': 'Lines',
                'data': 'lines_shipped',
                'name': 'lines_shipped'
            },
            {
                'title': 'Created by',
                'data': 'user_id',
                'name': 'user_id'
            },
            {
                'title': 'Order weight',
                'data': 'weight',
                'name': 'orders.weight',
                'visible': false
            },
            {
                'title': 'Package weight',
                'data': 'package_weight',
                'name': 'package_weight',
                'visible': false
            },
            {
                'title': 'Cost',
                'data': function(row, type, set, meta) {
                    const cost = row.cost

                    if (cost) {
                        return cost
                    }

                    if (window.dtInstances[selector].column(meta.col).visible()) {
                        window.dtInstances[selector].column(meta.col).visible(false)
                    }

                    window.setTimeout(function() {
                        $(`.colvisItem[data-resource="shipment"][data-index="${meta.col}"]`).parent().hide()
                    }, 1000)

                },
                'name': 'cost',
            },
            {
                'title': 'Tags',
                'data': 'tags',
                'visible': false,
                'orderable': false
            },
            {
                'title': 'Warehouse',
                'data': 'warehouse',
                'visible': true,
                'orderable': true
            },
            {
                'non_hiddable': true,
                "orderable": false,
                "title": "",
                "name": "",
                "data": function (data) {
                    if (data.voided_at) {
                        return `<div class="voided-label">VOIDED</div>`
                    } else {
                        if (data.allow_void_label) {
                            return app.tablePostButton(
                                `Are you sure you want to void this label?`,
                                data.void_link.title,
                                data.void_link,
                                'bg-red'
                            );
                        }
                    }

                    return null
                }
            }
        ]
    })

    $(document).ready(function() {
        dateTimePicker();
        dtDateRangePicker();
        $(document).find('select:not(.custom-select)').select2();
    })
}
