window.ReturnedProductReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    const selector = '#returned_product-table';

    window.datatables.push({
        selector: selector,
        resource: 'returned_product',
        ajax: {
            url: '/report/returned_product/data_table',
            data: function (data) {
                let request = {}
                filterForm
                    .serializeArray()
                    .map(function (input) {
                        request[input.name] = input.value;
                    });

                data.filter_form = request;

                window.exportFilters['returned_product'] = data;
            }
        },
        columns: [
            {
                "title": "SKU",
                'data': 'product_sku',
                "data": function (data) {
                    return `<a href="${data.returnedOrders.url}" target="_blank">${data.product_sku}</a>`;
                },
                "name": "product_sku"
            },
            {
                'title': 'Orders returned',
                'name': 'orders_returned',
                'data': 'orders_returned',
            },
            {
                'title': 'Units requested',
                'name': 'quantity_requested',
                'data': 'quantity_requested',
            },
            {
                'title': 'Units returned',
                'name': 'quantity_returned',
                'data': 'quantity_returned',
            }
        ],
    })

    $(document).ready(function () {
        dateTimePicker();
        dtDateRangePicker();
    })
}
