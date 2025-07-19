window.ToteLogReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const selector = '#tote_log-table';

    window.datatables.push({
        selector: selector,
        resource: 'tote_log',
        ajax: {
            url: '/report/tote_log/data_table/',
            data: function (data) {
                let request = {}
                filterForm
                    .serializeArray()
                    .map(function (input) {
                        request[input.name] = input.value;
                    });

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['tote_log'] = data;
            }
        },
        order: [6, 'desc'],
        columns: [
            {
                "title": "Tote name",
                "name": "totes.name",
                "data": function (data) {
                    return `<a href="${data.tote.url}">${data.tote.name}</a>`;
                }
            },
            {
                "title": "Order number",
                "data": function (data) {
                    return `<a href="${data.order.url}">${data.order.number}</a>`;
                },
                "name": "orders.number"
            },
            {
                "title": "SKU",
                "data": function (data) {
                    return `<a href="${data.product.url}">${data.product.sku}</a>`;
                },
                "name": "products.sku",
                "visible": false
            },
            {
                "title": "Quantity added",
                "data": "quantity",
                "name": "quantity",
                "class": "text-neutral-text-gray",
                "visible": false
            },
            {
                "title": "Quantity removed",
                "data": "quantity_removed",
                "name": "quantity_removed",
                "class": "text-neutral-text-gray",
                "visible": false
            },
            {
                "title": "Added to tote",
                "data": "created_at",
                "name": "created_at",
                "class": "text-neutral-text-gray",
                "visible": false
            },
            {
                "title": "Updated at",
                "data": "updated_at",
                "name": "updated_at",
                "class": "text-neutral-text-gray",
                "visible": false
            },
            {
                "title": "Picked by",
                "data": "picked_by",
                "name": "tote_order_items.user_id",
                "class": "text-neutral-text-gray",
                "visible": false
            }
        ]
    });

    $(document).ready(function () {
        dateTimePicker();
        dtDateRangePicker();
        $(document).find('select:not(.custom-select)').select2();
    })
}
