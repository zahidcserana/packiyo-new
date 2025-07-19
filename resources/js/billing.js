window.Billing = function () {
    $(document).ready(function() {
        let columns = [
            {
                "title": "",
                "data": function () {
                    return '<i class="ni ni-zoom-split-in preview-button"></i>'
                },
                "name": "customers.id"
            },
            {"title": "Name", "data": "name", "name": "contact_informations.name"},
            {"title": "Company Name", "data": "company_name", "name": "contact_informations.company_name"},
            {"title": "Invoice Count", "data": "invoices.count", "name": "invoices_count"},
        ];

        // START Hide/Show columns
        new ColumnVisibilityBeforeTableLoad(columns)
        // END Hide/Show columns

        $('#billing-table').DataTable(
            {
                serverSide: true,
                ajax: '/billings/data_table',
                responsive: true,
                pagingType: "simple",
                scrollX: true,
                pageLength: 20,
                search: {
                    search: $('#global-search-input').val()
                },
                sDom: '<"top">rt<"bottom"<"col col-12"ip>>',
                initComplete: function()
                {
                    const dtable = $("#billing-table").dataTable().api();
                    $(".dataTables_filter input")
                        .unbind() // Unbind previous default bindings
                        .bind("input", function (e) {
                            // Bind our desired behavior
                            // If the length is 3 or more characters, or the user pressed ENTER, search
                            if (this.value.length >= 1) {
                                // Call the API search function
                                dtable.search(this.value).draw();
                            }
                            // Ensure we clear the search if they backspace far enough
                            if (this.value === "") {
                                dtable.search("").draw();
                            }
                        });
                    // START Hide/Show columns
                    new ShowHideColumnInitComplete('#billing-table', 'user-settings/hide-columns',  'billings_table_hide_columns');
                    // END Hide/Show columns
                },
                createdRow: function( row, data, dataIndex ) {
                    $(row).attr( 'data-id', data['id'] );
                },
                columns: columns,
            }
        );

        let customerId = $('#customer-id').val();

        $('#customer-invoices-table').DataTable(
            {
                serverSide: true,
                ajax: '/billings/customer/'+customerId+'/invoices/customer_invoices_data/',
                responsive: true,
                initComplete: function()
                {
                    const dtable = $("#customer-invoices-table")
                        .dataTable()
                        .api();
                    $(".dataTables_filter input")
                        .unbind() // Unbind previous default bindings
                        .bind("input", function (e) {
                            // Bind our desired behavior
                            // If the length is 3 or more characters, or the user pressed ENTER, search
                            if (this.value.length >= 1) {
                                // Call the API search function
                                dtable.search(this.value).draw();
                            }
                            // Ensure we clear the search if they backspace far enough
                            if (this.value === "") {
                                dtable.search("").draw();
                            }
                        });
                },
                columns: [
                    {"title": "Date", "data": "date", "name": "date"},
                    {
                        "title": "Rate Card",
                        "data": function (data) {
                            return '<a href="' + data.rate_card['url'] + '">' + data.rate_card['name'] + '</a>'
                        },
                        "name": "rate_cards.name"
                    },
                    {
                        "title": "Direct Url",
                        "data": function (data) {
                            return '<a href="' + data.direct_url['url'] + '">' + data.direct_url['name'] + '</a>'
                        },
                        "name": "direct_url"
                    },
                    {
                        "orderable": false,
                        "class": "text-center",
                        "title": "Actions",
                        "data": function (data) {
                            return (
                                '<a href="' +
                                data["link_edit"] +
                                '" class="btn btn-primary edit" style="display: inline-block"> Edit </a>'
                            );
                        },
                    }
                ],
            }
        );

        $(document).on('click', '#billing-table .preview-button', function (event) {
            let id = $(event.target).closest('tr').attr('data-id');
            Preview.loadPreview(event, "billings/" + id + "/preview");
        });
    });
};
