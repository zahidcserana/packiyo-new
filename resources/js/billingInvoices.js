window.BillingInvoices = function (is_admin) {
    let customerId = $('#customer-id').val();
    let billId = $('#invoice-id').val();

    window.datatables.push({
        selector: '#invoice-line-items-table',
        resource: 'invoice-line-items',
        ajax: {
            url: '/billings/customers/' + customerId + '/invoices/' + billId + '/items_data_table/'
        },
        columns: [
            {
                "orderable": false,
                "class":"text-left",
                "title": "",
                "data": function (data) {
                    return `<button class="table-icon-button" type="button" data-invoice-id="${data.invoice_id}" data-id="${data.id}" data-toggle="modal" data-target="#edit-invoice-line-item-modal">
                        <i class="picon-edit-filled icon-lg" title="Edit"></i>
                    </button>`;
                },
                "name": "invoice_line_items.id"
            },
            {"title": "Charge Type", "data": "billing_rate_name", "name": "billing_rates.name"},
            {"title": "Invoice Code", "data": "billing_rate_code", "name": "billing_rates.code"},
            {"title": "Added At", "data": "created_at", "name": "invoice_line_items.created_at"},
            {"title": "Description", "data": "description", "name": "invoice_line_items.description"},
            {"title": "Total Price", "data": "total_charge", "name": "invoice_line_items.charge_per_unit"},
            {
                "orderable": false,
                "class":"text-center",
                "title": "Actions",
                "data": function (data) {
                    return app.tableDeleteButton(
                        `Are you sure you want to delete this item?`,
                        data.link_delete
                    )
                },
            },
        ],
        createdRow: function( row, data, dataIndex ) {
            $(row).attr( 'data-id', data['id'] );
            if (data.billing_rate_type === 'shipping_rates') {
                let shippingCarrierTd;
                let shippingMethodTd;

                if (data.carrier !== "") {
                    shippingCarrierTd = '<td>' + data.carrier + '</td>';
                } else {
                    shippingCarrierTd = '<td class="bg-red text-white">RECALCULATE</td>';
                }

                if (data.services !== "") {
                    shippingMethodTd = '<td>' + data.services + '</td>';
                } else {
                    shippingMethodTd = '<td class="bg-red text-white">RECALCULATE</td>';
                }

                $('#invoice-line-items-table').DataTable().row(dataIndex).child('<tr>'
                    + '<td colspan="3">'
                    + '<table class="table align-items-center col-12 p-0">'
                    + '<thead class="">'
                    + '<tr>'
                    + '<th>Client Name</th>'
                    + '<th>Client Order Reference</th>'
                    + '<th>Delivery Name</th>'
                    + '<th>Delivery Address</th>'
                    + '<th>Country</th>'
                    + '<th>Carrier</th>'
                    + '<th>Services</th>'
                    + '<th>Weight</th>'
                    + '<th>Tracking Number</th>'
                    + '<th>Number of units in shipment</th>'
                    + '<th>Date Dispatched</th>'
                    + '</tr>'
                    + '</thead>'
                    + '<tbody>'
                    + '<tr>'
                    + '<td>' + data.client_name + '</td>'
                    + '<td>' + data.client_order_reference + '</td>'
                    + '<td>' + data.delivery_name + '</td>'
                    + '<td>' + data.delivery_address + '</td>'
                    + '<td>' + data.country + '</td>'
                    + shippingCarrierTd
                    + shippingMethodTd
                    + '<td>' + data.weight + '</td>'
                    + '<td>' + data.tracking_number + '</td>'
                    + '<td>' + data.number_of_units_in_shipment + '</td>'
                    + '<td>' + data.date_dispatched + '</td>'
                    + '</tr>'
                    + '</tbody>'
                    + '</table>'
                    + '</td>'
                    + '</tr>').show();
            }
        },
    });

    $(document).ready(function () {
        $('#edit-invoice-line-item-modal').on('show.bs.modal', function (e) {
            $('.edit-invoice-line-item-modal').remove();

            $('#edit-invoice-line-item-modal .modal-content').html(`<div class="spinner">
                <img src="/img/loading.gif">
            </div>`);

            let itemId = $(e.relatedTarget).data('id');
            let billId = $(e.relatedTarget).data('invoice-id');

            $.ajax({
                type:'GET',
                serverSide: true,
                url:'/invoices/' + billId + '/getEditInvoiceLineItemForm/' + itemId,
                success:function(data) {
                    $('#edit-invoice-line-item-modal > div').html(data);
                },
                error: function (response) {
                    let modal = $('#edit-invoice-line-item-modal');

                    appendValidationMessages(modal, response)
                }
            });
        })

        let columns = [
            {
                "title": "Export",
                "data": function (data) {
                    return '<a href="/invoices/' + data['id'] + '/export_csv" target="_blank" style="display: inline-block" class="ignore"> Export </a>';
                },
                "name": "id"
            },
            {
                "title": "Export Invoice",
                "data": function (data) {
                    return '<a href="/invoices/' + data['id'] + '/export_invoice_pdf" target="_blank" style="display: inline-block" class="ignore"> Export Invoice PDF</a>';
                },
                "name": "id"
            },
            {
                "title": "Invoice Number",
                "data": "invoice_number",
                "name": "invoice_number"
            },
            {
                "title": "Period Start",
                "data": "period_start",
                "name": "invoices.period_start"
            },
            {
                "title": "Period End",
                "data": "period_end",
                "name": "invoices.period_end"
            },
            {
                "title": "Amount",
                "data": "amount",
                "name": "invoices.amount"
            },
            {
                "title": "Status",
                "data": "status",
                "name": "invoices.status"
            },
        ];

        if (is_admin) {
            columns.unshift(
                {
                    "title": "Customer",
                    "data": function (data) {
                        return '<a href="' + data['customer']['url'] + '" style="display: inline-block"> ' + data['customer']['name'] + ' </a>';
                    },
                    "name": "invoices.customer_id"
                },
                {
                    "title": "Primary Rate Card",
                    "data": function (data) {
                        return '<a href="' + data['primary_rate_card']['url'] + '" style="display: inline-block"> ' + data['primary_rate_card']['name'] + ' </a>';
                    },
                    "name": "invoices.rate_cards.id"
                },
                {
                    "title": "Secondary Rate Card",
                    "data": function (data) {
                        return '<a href="' + data['secondary_rate_card']['url'] + '" style="display: inline-block"> ' + data['secondary_rate_card']['name'] + ' </a>';
                    },
                    "name": "invoices.rate_cards.id"
                });
        }

        $('#invoices-table').DataTable(
            {
                serverSide: true,
                ajax: '/billings/invoices/data_table',
                responsive: true,
                search: {
                    search: JSON.stringify({
                        filterArray: [{
                            columnName: 'dates_between',
                            value: $('.dates_between').val()
                        }]
                    })
                },
                pagingType: "simple",
                scrollX: true,
                pageLength: 20,
                sDom: '<"top">rt<"bottom"<"col col-12"p>>',
                language: {
                    paginate: {
                        previous: "<i class=\"picon-arrow-backward-light icon-lg\"></i>",
                        next: "<i class=\"picon-arrow-forward-light icon-lg\"></i>"
                    }
                },
                drawCallback: function(  ) {
                    $('.loading-container').removeClass('d-flex').addClass('d-none');
                },
                columns: columns,
                createdRow: function (row, data, dataIndex) {
                    $(row).attr('data-id', data['id']);
                    $(row).attr('data-customer-id', data['customer']['id']);
                },
            }
        );

        $(document).on('click', '#invoices-table tbody tr', function (event) {
            if ($(this).find('.dataTables_empty').length) return false;

            if (document.getSelection().toString() === '') {
                let id = $(event.target).closest('tr').attr('data-id')
                let customerId = $(event.target).closest('tr').attr('data-customer-id')

                if (typeof id !== 'undefined' && $(event.target)[0].className !== 'ignore') {
                    window.location.href = '/billings/customers/' + customerId + '/invoices/' + id + '/items'
                }
            }
        });
    });
};
