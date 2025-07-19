window.PickingBatchReport = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm)
    const tableSelector = 'picking_batch';

    window.datatables.push({
        selector: '#' + tableSelector + '-table',
        resource: 'picking_batch',
        ajax: {
            url: '/report/picking_batch/data_table',
            data: function (data) {
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['picking_batch'] = data
            }
        },
        columns: [
            {
                "title": "ID",
                "name": "picking_batches.id",
                "data": function (data) {
                    return `<a href="${data.link_items}">${data.picking_batch_id}</a>`
                }
            },
            {
                'title': 'Start date/time',
                'name': 'picking_batches.created_at',
                'data': 'start_date_time',
            },
            {
                'title': 'Last action date/time',
                'name': 'picking_batch_items.updated_at',
                'data': 'last_action_date_time',
            },
            {
                'title': 'Batch time',
                'name': 'picking_batch_items.updated_at',
                'data': 'batch_time',
            },
            {
                'title': 'Time per Pick',
                'name': 'picking_batch_items.updated_at',
                'data': 'time_per_pick',
            },
            {
                'title': 'Total products in batch',
                'name': 'total_products_in_batch',
                'data': 'total_products_in_batch',
                "orderable": false,
            },
            {
                'title': 'Total picked products',
                'name': 'total_picked_products',
                'data': 'total_picked_products',
                "orderable": false,
            },
            {
                'title': 'Amount of orders',
                'name': 'amount_of_orders',
                'data': 'amount_of_orders',
                "orderable": false,
            },
            {
                'title': 'User',
                'name': 'tote_order_items.user_id',
                'data': 'user',
                "orderable": false,
            },
            {
                'title': 'Tag ',
                'data': 'tag_name',
                'name': 'tag_name',
            },
            {
                'title': 'No single line orders',
                'data': 'exclude_single_line_orders',
                'name': 'exclude_single_line_orders',
            },
            {
                'non_hiddable': true,
                "orderable": false,
                "title": "Action",
                "name": "picking_batches.id",
                "data": function (data) {
                    if (data.is_active) {
                        let clearButton = app.tablePostButton(
                            `Are you sure you want to clear the batch?`,
                            'Clear batch',
                            data.link_clear_batch,
                            'bg-logoOrange',
                            true
                        );

                        if (data.is_deleted) {
                            return '';
                        } else {
                            return clearButton;
                        }
                    }

                    return '';
                }
            }
        ],
    })

    $(document).ready(function () {
        dateTimePicker();
        dtDateRangePicker();

        $('#' + tableSelector + '-table').on('packiyo:ajax-success', 'form.ajax-form', function () {
            window.dtInstances['#' + tableSelector + '-table'].ajax.reload();
        });
    })
}
