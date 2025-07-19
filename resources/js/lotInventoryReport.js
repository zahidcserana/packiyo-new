window.LotInventoryReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const tableSelector = 'lot_inventory';

    window.datatables.push({
        selector: '#' + tableSelector + '-table',
        resource: 'lot_inventory',
        ajax: {
            url: '/report/lot_inventory/data_table',
            data: function (data) {
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['lot_inventory'] = data
            }
        },
        columns: [
            {
                'title': 'Lot ID',
                'name': 'lots.name',
                'data': 'lot.name',
            },
            {
                'title': 'Product name',
                'name': 'products.name',
                'data': function (data) {
                    return `<a href="${data.product.url}">${data.product.name}</a>`;
                },
                'visible': false
            },
            {
                'title': 'SKU',
                'name': 'products.sku',
                'data': function (data) {
                    return `<a href="${data.product.url}">${data.product.sku}</a>`;
                }
            },
            {
                'title': 'Location',
                'name': 'locations.name',
                'data': 'location.name'
            },
            {
                'title': 'Expiration date',
                'name': 'lots.expiration_date',
                'data': 'lot.expiration_date',
            },
            {
                'title': 'On hand',
                'name': 'quantity_remaining',
                'data': 'on_hand',
                'orderable': true,
            },
            {
                'title': 'Item price',
                'data': 'lot.item_price',
                'name': 'lots.item_price',
            },
            {
                'title': 'Lot value',
                'data': 'lot_value',
                'orderable': false,
            },
            {
                'title': 'Warehouse',
                'name': 'contact_informations.name',
                'data': 'warehouse.name'
            }
        ],
    })

    $(document).ready(function () {
        dateTimePicker();
        dtDateRangePicker();
        $(document).find('select:not(.custom-select)').select2();

        $('#' + tableSelector + '-table').on('packiyo:ajax-success', 'form.ajax-form', function () {
            window.dtInstances['#' + tableSelector + '-table'].ajax.reload();
        });
    })
}
