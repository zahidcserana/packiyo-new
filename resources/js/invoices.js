window.Invoices = function () {
    window.datatables.push({
        selector: '#invoices-table',
        resource: 'invoices',
        ajax: {
            url: '/bulk_invoice_batches/data_table/'
        },
        columns: [
            {
                "title": "",
                "data": function (data) {
                    return '<a href="' + data['link_edit'] + '" class="table-icon-button" type="button"><i class="picon-edit-filled icon-lg" title="Edit"></i></a>';
                },
                "orderable": false,
                "name": "bulk_invoice_batches.id"
            },
            {
                "title": "Invoice batch number",
                "data": "number",
                "name": "number",
                "orderable": true,
                'width': '50%'
            },
            {
                "title": "Customer",
                "name": "name",
                "orderable": false,
                "data": function (data) {
                    return `<span title="${data['other_customers'] ?? data['name']}">${data['name']}</span>`;
                },
            },
            {
                "title": "Status",
                "data": "status",
                "name": "status",
                "orderable": false
            },
            {
                "title": "Amount",
                "data": "amount",
                "name": "amount",
                "orderable": false
            },
            {
                "title": "Date period",
                "data": "period",
                "name": "period",
                "orderable": false
            },
            {
                "title": "Last Modified",
                "data": "updated_at",
                "name": "updated_at",
                "orderable": false
            },
            {
                "orderable": false,
                "class":"text-center",
                "title": "Actions",
                "data": function (data) {
                    const printButton = `
                        <a class="pr-2" target="_blank" href="/bulk_invoice_batches/${data['id']}/export"><i class="picon-printer-light icon-lg" title="Export"></i></a>
                    `;

                    const linkFinalize = data['link_finalize'];
                    const finalizeButton = `
                        <form action="${linkFinalize.url}" method="post" class="d-inline-block">
                            <input type="hidden" name="_method" value="patch">
                            <input type="hidden" name="_token" value="${linkFinalize.token}">
                            <button type="submit" class="table-icon-button">
                                <i class="picon-check-circled-light icon-lg" title="Finalize"></i>
                            </button>
                        </form>
                    `;

                    const linkRecalculate = data['link_recalculate'];
                    const recaulculateButton = `
                        <form action="${linkRecalculate.url}" method="post" class="d-inline-block">
                            <input type="hidden" name="_method" value="patch">
                            <input type="hidden" name="_token" value="${linkRecalculate.token}">
                            <button type="submit" class="table-icon-button">
                                <i class="picon-reload-light icon-lg" title="Recalculate"></i>
                            </button>
                        </form>
                    `;


                    const deleteButton = app.tableDeleteButton(
                        'Are you sure you want to delete it?',
                        data.link_delete
                    );

                    return printButton + finalizeButton + recaulculateButton + deleteButton;
                },
            },
        ],
        createdRow: function( row, data, dataIndex ) {
            $(row).attr( 'data-edit-link', data['link_edit'] );
        },
    })
};
