window.BillingCustomers = function () {
    if (typeof customerId === 'undefined') {
        window.datatables.push({
            selector: '#customers-table',
            resource: 'billing-customers',
            ajax: {
                url: '/billings/customers/data-table/'
            },
            columns: [
                {
                    "title": "",
                    "data": function (data) {
                        return '<a href="customers/' + data['id'] + '/invoices" class="table-icon-button" type="button"><i class="picon-edit-filled icon-lg" title="Edit"></i></a>';
                    },
                    "orderable": false,
                    "name": "customers.id"
                },
                {
                    "title": "Name",
                    "data": "name",
                    "name": "contact_informations.name"
                },
                {
                    "title": "Rate Card",
                    "data": function (data) {
                        if (!data['primary_rate_card']['url']) return ''

                        return '<a href="' + data['primary_rate_card']['url'] + '" style="display: inline-block"> ' + data['primary_rate_card']['name'] + ' </a>';
                    },
                    "name": "customers.primary_rate_card",
                    "orderable": false
                },
                {
                    "title": "Last Invoice Amount",
                    "data": function (data) {
                        return '<a href="' + data['last_billed']['url'] + '" style="display: inline-block"> ' + data['last_billed']['amount'] + ' </a>';
                    },
                    "name": "customers.amount"
                },
                {
                    "title": "Last Invoice Period",
                    "data": function (data) {
                        return data['last_billed']['period_start'] + ' - ' + data['last_billed']['period_end'];
                    },
                    "name": "customers.last_invoice"
                },
                {
                    "title": "Last Invoice Calculated",
                    "data": 'last_billed.calculated_at',
                    "name": "customers.last_billed"
                },
            ],
        });
    }

    if (typeof customerId != 'undefined') {
        window.datatables.push({
            selector: '#customer-invoices-table',
            resource: 'customer-invoices',
            ajax: {
                url: '/billings/customers/'+ customerId +'/invoices/data_table/'
            },
            columns: [
                {
                    "title": "",
                    "data": function (data) {
                        return '<a href="/billings/customers/' + customerId + '/invoices/' + data['id'] + '/items" class="table-icon-button" type="button"><i class="picon-edit-filled icon-lg" title="Edit"></i></a>';
                    },
                    "orderable": false,
                    "name": "id"
                },
                {"title": "Invoice Number", "data": "invoice_number", "name": "invoice_number"},
                {"title": "Rate Card", "data": "primary_rate_card", "name": "primary_rate_card", "orderable": false},
                {"title": "Amount", "data": "amount", "name": "amount"},
                {
                    "title": "Date Period",
                    "data": function(data) {
                        return data['period_start'] + ' - ' + data['period_end'];
                    },
                    "name": "customers.amount",
                    "orderable": false
                },
                {"title": "Last updated at", "data": "calculated_at", "name": "calculated_at"},
                {"title": "Invoice Status", "data": "status", "name": "status"},
                {"title": "Actions",
                    "orderable": false,
                    "data": function (data) {
                        let token = $('meta[name="csrf-token"]').attr('content');

                        let exportBtn = '<a class="pr-2" target="_blank" href="/invoices/' + data['id'] + '/export_csv"><i class="picon-printer-light icon-lg" title="Export"></i></a>'

                        let recalculateBtn = '<a class="pr-2" href="#" data-toggle="modal" '+
                            ' data-target="#invoice-recalculate-modal" data-invoice-number="'+data['invoice_number']+
                            '" data-invoice-id="'+data['id']+'"'+ ' data-period-start="'+data['period_start']+'" data-period-end="'+data['period_end']+'">'+
                            ' <i class="picon-reload-light icon-lg" title="Recalculate"></i></a>'


                        let deleteBtn = '<form method="POST" action="/invoices/' + data.id + '" style="display: inline-block">' +
                            '<input type="hidden" name="_token" value="'+ token +'"/>' +
                            '<input type="hidden" name="_method" value="delete"/>' +
                            '<button type="submit" class="table-icon-button"><i class="picon-trash-light icon-lg" title="Delete"></i></button>' +
                            '</form>'

                        recalculateBtn = data.is_readonly_user || data.is_finalized ? '' : recalculateBtn;
                        deleteBtn = data.is_readonly_user || data.is_finalized ? '' : deleteBtn;

                        return exportBtn + recalculateBtn + deleteBtn
                    }
                },
            ],
            createdRow: function( row, data, dataIndex ) {
                $(row).attr( 'data-id', data['id'] );
            },
        })
    }

    $(document).ready(function() {
        let selectablesTables =  $('.selectables-table')

        $('[name="create_recalculate"]').on('click', function() {
            let formToHide = $(this).val()
            let formToShow = $(this).attr('id') + '_form'

            $('.' + formToHide).hide()
            $('.' + formToShow).show()

            selectablesTables.each(function () {
                $(this).trigger('draw.dt')
            })
        })

        $('.reset').on('click', function () {
            let form = $(this).closest('form')

            form[0].reset()
            $(form).find('.customers_selectables').val('[]')

            selectablesTables.each(function () {
                $(this).trigger('draw.dt')
            })
        })

        $('.select-all').on('click', function () {
            let selectedForm = $('[name="create_recalculate"]:checked')
            let checkBoxes = selectablesTables.find('.item-checkbox')
            let dateToCompare = $('#date_to_compare').val()

            if (selectedForm.attr('id') === 'create') {
                if (!dateToCompare) return alert('Start date is empty')

                checkBoxes.each(function () {
                    let parentRow =  $(this).parent()
                    let lastInvoiceDate = $(parentRow).find('.last-invoice-date').val()

                    // uncheck if checked
                    if ($(this).is(':checked')) $(this).trigger('click')

                    if (dateToCompare && dateToCompare === lastInvoiceDate) $(this).trigger('click')
                })
            }

            if (selectedForm.attr('id') === 'recalculate') {
                checkBoxes.each(function () {
                    // uncheck if checked
                    if($(this).is(':checked')) $(this).trigger('click')

                    $(this).trigger('click')
                })
            }
        })

        function GetFromSelectedForm(table) {
            let selectedCheckbox = $('[name="create_recalculate"]:checked')
            let selectedFormClassName = '.' + selectedCheckbox.attr('id') + '_form'

            this.selectedCheckbox = selectedCheckbox
            this.selectablesInput = $(selectedFormClassName + ' .' + table.attr('data-selectables'))
            this.selectables = this.selectablesInput.val() ?
                JSON.parse(this.selectablesInput.val()) : '[]'
        }

        function updateTotalSelectedCount() {
            $('.customers_selectables').each(function () {
                    let count = JSON.parse($(this).val()).length
                    $('.' + $(this).attr('data-total-class')).text(count)
            })
        }

        updateTotalSelectedCount()

        selectablesTables.each(function () {
            if ($(this).attr('data-url')) {
                let selectablesUrl = $(this).attr('data-url')
                let selectablesTable = $(this)

                let selectablesColumns = [
                    {
                        "title": "",
                        "data": function (data) {
                            return '<a href="customers/' + data['id'] + '/invoices" class="table-icon-button" type="button"><i class="picon-edit-filled icon-lg" title="Edit"></i></a>';
                        },
                        "name": "customers.id"
                    },
                    // {
                    //     "title": "",
                    //     "data": function(data) {
                    //         let form = new GetFromSelectedForm(selectablesTable)
                    //         let isChecked = form.selectables.includes(String(data['id'])) ? 'checked' : ''
                    //         let lastInvoiceDate = '<input class="last-invoice-date" type="hidden" value="' + data['last_billed']['period_end'] + '">'
                    //         let checkboxInput = '<input ' +
                    //             'class="item-checkbox ignore" ' +
                    //             'type="checkbox" ' +
                    //             '' + isChecked + ' ' +
                    //             'value="'+data['id']+'" ' +
                    //             '/>'
                    //
                    //         return data['primary_rate_card']['name'] ? checkboxInput + lastInvoiceDate : '-'
                    //     },
                    //     "name": "contact_informations.name"
                    // },
                    {
                        "title": "Name",
                        "data": "name",
                        "name": "contact_informations.name"
                    },
                    {
                        "title": "Primary Rate Card",
                        "data": function(data) {
                            if (!data['primary_rate_card']['url']) return ''

                            return '<a href="' + data['primary_rate_card']['url'] + '" style="display: inline-block"> ' + data['primary_rate_card']['name'] + ' </a>';
                        },
                        "name": "customers.id"
                    },
                    {
                        "title": "Secondary Rate Card",
                        "data": function(data) {
                            if (!data['secondary_rate_card']['url']) return ''

                            return '<a href="' + data['secondary_rate_card']['url'] + '" style="display: inline-block"> ' + data['secondary_rate_card']['name'] + ' </a>';
                        },
                        "name": "customers.id"
                    },
                    {
                        "title": "Last Invoice Amount",
                        "data": function(data) {
                            return '<a href="' + data['last_billed']['url'] + '" style="display: inline-block"> ' + data['last_billed']['amount'] + ' </a>';
                        },
                        "name": "customers.id"
                    },
                    {
                        "title": "Last Invoice Period",
                        "data": function(data) {
                            return data['last_billed']['period_start'] + ' - ' + data['last_billed']['period_end'];
                        },
                        "name": "customers.id"
                    },
                    {
                        "title": "Last Invoice Calculated",
                        "data": 'last_billed.calculated_at',
                        "name": "customers.id"
                    },
                ];

                selectablesTable.DataTable(
                    {
                        serverSide: true,
                        ajax: selectablesUrl,
                        responsive: true,
                        pagingType: "full_numbers",
                        scrollX: true,
                        pageLength: 20,
                        sDom: '<"top">rt<"bottom"<"col col-12"p>>',
                        language: {
                            paginate: {
                                previous: "<i class=\"picon-arrow-backward-light icon-lg\"></i>",
                                next: "<i class=\"picon-arrow-forward-light icon-lg\"></i>"
                            }
                        },
                        columns: selectablesColumns,
                        drawCallback: function(  ) {
                            $('.loading-container').removeClass('d-flex').addClass('d-none');
                        },
                        createdRow: function( row, data, dataIndex ) {
                            $(row).find('.item-checkbox').on('click', function (event){
                                event.preventDefault()
                                let checkDates = $('[name="create_recalculate"]:checked').attr('id') === 'create'
                                let form = new GetFromSelectedForm(selectablesTable)
                                let selectablesInput = form.selectablesInput
                                let selectables = form.selectables

                                let dateToCompare = $('#date_to_compare')
                                let customerInvoiceDate = $(this).closest('td').find('.last-invoice-date')

                                if ($(this).is(':checked')) {
                                    if (
                                        (dateToCompare.val() === customerInvoiceDate.val() && checkDates)
                                        || (!dateToCompare.val() && checkDates)
                                        || !checkDates
                                    ) {
                                        if (!dateToCompare.val().length) {
                                            dateToCompare.val(customerInvoiceDate.val())
                                        }

                                        if(!selectables.includes($(this).val())) {
                                            selectables.push($(this).val())
                                        }
                                    } else {
                                        alert('Period end doesnt match!')
                                    }
                                } else {
                                    if(selectables.includes($(this).val())) {
                                        selectables.splice(selectables.indexOf($(this).val()), 1)
                                    }
                                }

                                selectablesInput.val(JSON.stringify(selectables))

                                if (selectablesInput.val() === '[]') {
                                    dateToCompare.val('')
                                }

                                selectablesTable.DataTable().draw(false)
                                updateTotalSelectedCount()
                            });

                            $(row).attr( 'data-id', data['id'] );
                            $(row).find('.item-checkbox').parent().addClass('ignore')
                        },
                    });

                selectablesTable.on('draw.dt', function () {
                    let form = new GetFromSelectedForm(selectablesTable)
                    let selectables = form.selectables

                    selectablesTable.find('.item-checkbox').each(function () {
                        let checkbox = $(this)
                        let tableRow = $(this).closest('tr')
                        let count = Number(tableRow.find('.count').html())
                        let totalCount = Number(tableRow.find('.totalCount').html())
                        if (count && totalCount) {
                            if (count === totalCount && !checkbox.is(':checked')) {
                                checkbox.trigger('click')
                            }

                            if (count !== totalCount && checkbox.is(':checked')) {
                                selectables.splice(selectables.indexOf(checkbox.val()), 1)
                                checkbox.prop('checked', false)
                            }
                        }

                        if (selectables.includes($(this).val())) {
                            checkbox.prop('checked', true)
                        } else {
                            checkbox.prop('checked', false)
                        }
                    })

                    updateTotalSelectedCount()
                })

                $('.datetimepicker').each(function () {
                    $(this).daterangepicker({
                        singleDatePicker: true,
                        timePicker: false,
                        autoApply: true,
                        autoUpdateInput:false,
                        locale: {
                            format: 'Y-MM-DD'
                        }
                    });

                    $(this).on('apply.daterangepicker', function(ev, picker) {
                        $(this)
                            .val(moment(picker.startDate).format('Y-MM-DD'))
                    });
                })
            }
        });
    });
};
