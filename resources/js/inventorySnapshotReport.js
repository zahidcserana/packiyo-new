window.InventorySnapshotReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const selector = '#inventory_snapshot-table';

    window.datatables.push({
        selector: selector,
        resource: 'inventory_snapshot',
        ajax: {
            url: '/report/inventory_snapshot/data_table',
            data: function(data){
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['inventory_snapshot'] = data
            }
        },
        order: [3, 'desc'],
        columns: [
            {
                "title": "Image",
                "name": "products.image",
                "orderable": false,
                "searchable": false,
                "data": function (data) {
                    return `
                            <a href="#" title="{{ __('Show image') }}" data-toggle="modal" data-target="#big-image-modal" data-image="${data.image}">
                                <img src="${data.image}" class="img-thumbnail" />
                            </a>
                        `
                },
            },
            {
                "title": "Name",
                "data": function (data) {
                    return `<a href="${data.link_edit}">${data.name}</a>`;
                },
                "name": "product.name",
                'visible': false,
                'orderable': true,
            },
            {
                "title": "SKU",
                "data": function (data) {
                    return `<a href="${data.link_edit}">${data.sku}</a>`;
                },
                "name": "product.sku",
                'visible': false,
                'orderable': true,
            },
            {
                "title": "Customer",
                "data": 'customer',
                "name": "customer.id",
                "visible": false,
                "orderable": true,
                "searchable": false,
            },
            {
                "title": "Warehouse",
                "data": 'warehouse',
                "name": "warehouse.id",
                "visible": false,
                "orderable": true,
                "searchable": false,
            },
            {
                "title": "On Hand",
                "data": "quantity_on_hand",
                "name": "inventory.last",
                'visible': false,
                "orderable": true,
            },
            {
                "title": "Location",
                "data": "location_name",
                "name": "location.name",
                'visible': false,
                "orderable": true,
            },
        ]
    })

    $(document).ready(function() {
        dtDateRangePicker({
            maxDate: moment().subtract(1, 'days').format('YYYY-MM-DD')
        })
        $(document).find('select:not(.custom-select)').select2()
    })
}
