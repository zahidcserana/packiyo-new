window.DuplicateBarcodeReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const selector = '#duplicate_barcode-table';

    window.datatables.push({
        selector: selector,
        resource: 'duplicate_barcode',
        ajax: {
            url: '/report/duplicate_barcode/data_table',
            data: function(data){
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['duplicate_barcode'] = data
            }
        },
        initComplete: function (settings, json) {
            const dtable = window.dtInstances[selector];

            if (!json.data[0].customer.is_3pl_child) {
                dtable.column(0).visible(false)
            }
        },
        order: [3, 'desc'],
        columns: [
            {
                'title': 'Customer',
                'name': 'customer',
                'data': function ( data, type, row, meta ) {
                    return '<a href="' + data['customer']['url'] + '" target="_blank">' + data['customer']['name'] + '</a>';
                }
            },
            {
                'title': 'Product Names',
                'name': 'name',
                'data': function (data) {
                    let productNames = ''

                    data['products'].forEach(function (product) {
                        productNames += '<a href="' + product.url + '" target="_blank">' + product.name + '</a><br/>'
                    })

                    return productNames
                },
            },
            {
                'title': 'SKU',
                'name': 'sku',
                'data': function (data) {
                    let productSkus = ''

                    data['products'].forEach(function (product) {
                        productSkus += '<a href="' + product.url + '" target="_blank">' + product.sku + '</a><br/>'
                    })

                    return productSkus
                }
            },
            {
                'title': 'Barcode',
                'name': 'barcode',
                'data': 'barcode',
            }
        ]
    })

    $(document).ready(function() {
        dateTimePicker()
        dtDateRangePicker()
        $(document).find('select:not(.custom-select)').select2()
    })
}
