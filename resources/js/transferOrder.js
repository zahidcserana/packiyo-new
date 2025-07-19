window.TransferOrder = function () {
    const filterForm = $('#toggleFilterForm').find('form')
    window.loadFilterFromQuery(filterForm);

    window.datatables.push({
        selector: '#transfer-orders-table',
        resource: 'transfer-orders',
        ajax: {
            url: '/transfer_orders/data-table',
            data: function (data) {
                let request = window.serializeFilterForm(filterForm)

                data.filter_form = request

                window.queryUrl(request)

                window.exportFilters['transfer-orders-table'] = data
            }
        },
        columns: [
            {
                "title": "Transfer Order",
                "data": function (data) {
                    return `
                            <a href="${data.link_edit}">${data.number}</a>
                        `
                },
                "name": "number"
            },
            {
                "title": "Created",
                "data": "ordered_at",
                "name": "ordered_at"
            },
            {
                "title": "Status",
                "data": "status",
                "name": "status"
            },
            {
                "title": "From",
                "data": "from_warehouse",
                "name": "from_warehouse"
            },
            {
                "title": "To",
                "data": "to_warehouse",
                "name": "to_warehouse"
            },
            {
                "title": "Items ordered",
                "data": "items_ordered",
                "name": "items_ordered"
            },
            {
                "title": "Items received",
                "data": "items_received",
                "name": "items_received"
            },
            {
                "non_hiddable": true,
                "searchable": false,
                "orderable": false,
                "class": "text-right",
                "data": function (data) {
                    if (data.is_client) {
                        return
                    }

                    return `
                        <a
                            type="button"
                            class="btn bg-logoOrange text-white px-5 font-weight-700"
                            href="${data.link_receive}"
                            title="Receive transfer order"
                        >
                            Receive
                        </a>
                        <form action="${ data.link_close_po.url }" method="post" class="d-inline-block">
                            <input type="hidden" name="_token" value="${data.link_close_po.token}">
                            <button type="button" class="btn bg-logoOrange text-white px-5 font-weight-700" data-confirm-message="Are you sure you want to close this transfer order" data-confirm-button-text="Yes" title="Close transfer order">
                                Close transfer order
                            </button>
                        </form>
                    `
                }
            }
        ]
    })
}
