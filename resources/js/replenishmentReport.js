window.ReplenishmentReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const selector = '#replenishment-table';

    window.datatables.push({
        selector: selector,
        resource: 'replenishment',
        ajax: {
            url: '/report/replenishment/data_table',
            data: function(data){
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['replenishment'] = data
            }
        },
        order: [1, 'desc'],
        columns: [
            {
                'title': 'Product Name',
                'name': 'name',
                'data': function (data) {
                    return '<a href="' + data['product_url'] + '" target="_blank">' + data['product_name'] + '</a>';
                },
            },
            {
                'title': 'SKU',
                'name': 'sku',
                'data': function (data) {
                    return '<a href="' + data['product_url'] + '" target="_blank">' + data['sku'] + '</a>';
                }
            },
            {
                'title': 'On Hand',
                'name': 'quantity_on_hand',
                data: function(data) {
                    if (data.product_warehouses) {
                        let tooltipTitle = '';
                        data.product_warehouses.map(function(productWarehouse){
                            tooltipTitle += productWarehouse.warehouse_name + ' - ' + productWarehouse.quantity_on_hand + '<br/>';
                        });

                        return `
                            <i class="picon-alert-circled-light mr-1" data-toggle="tooltip" data-placement="top" data-html="true" title="${escapeQuotes(tooltipTitle)}"></i>
                            ${data.quantity_on_hand}
                        `
                    }

                    return data.quantity_on_hand
                }
            },
            {
                'title': 'Allocated',
                'name': 'quantity_allocated',
                data: function(data) {
                    if (data.product_warehouses) {
                        let tooltipTitle = '';
                        data.product_warehouses.map(function(productWarehouse){
                            tooltipTitle += productWarehouse.warehouse_name + ' - ' + productWarehouse.quantity_allocated + '<br/>';
                        });

                        return `
                            <i class="picon-alert-circled-light mr-1" data-toggle="tooltip" data-placement="top" data-html="true" title="${escapeQuotes(tooltipTitle)}"></i>
                            ${data.quantity_allocated}
                        `
                    }

                    return data.quantity_allocated
                }
            },
            {
                'title': 'Pickable amount',
                'name': 'quantity_pickable',
                data: function(data) {
                    if (data.product_warehouses) {
                        let tooltipTitle = '';
                        data.product_warehouses.map(function(productWarehouse){
                            tooltipTitle += productWarehouse.warehouse_name + ' - ' + productWarehouse.quantity_pickable + '<br/>';
                        });

                        return `
                            <i class="picon-alert-circled-light mr-1" data-toggle="tooltip" data-placement="top" data-html="true" title="${escapeQuotes(tooltipTitle)}"></i>
                            ${data.quantity_pickable}
                        `
                    }

                    return data.quantity_pickable
                }
            },
            {
                'title': 'QTY to move',
                data: function(data) {
                    if (data.product_warehouses) {
                        let tooltipTitle = '';
                        data.product_warehouses.map(function(productWarehouse){
                            tooltipTitle += productWarehouse.warehouse_name + ' - ' + productWarehouse.quantity_to_replenish + '<br/>';
                        });

                        return `
                            <i class="picon-alert-circled-light mr-1" data-toggle="tooltip" data-placement="top" data-html="true" title="${escapeQuotes(tooltipTitle)}"></i>
                            ${data.quantity_to_replenish}
                        `
                    }

                    return data.quantity_to_replenish
                },
                'orderable': false,
            },
            {
                'title': 'Locations',
                'data': 'locations',
                'orderable': false,
            }
        ]
    })

    $(document).ready(function() {
        dateTimePicker();
        dtDateRangePicker();
        $(document).find('select:not(.custom-select)').select2();
    });
}
