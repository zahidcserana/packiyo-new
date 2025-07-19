window.BillingReportDataTableAddons = function () {
    $(document).ready(function() {
        let from = moment().startOf('month')
        let to = moment()
        let datetimepickerElement = $('.table-datetimepicker');

        if (datetimepickerElement.is('[name="dates_between"]') && datetimepickerElement.val().length > 0) {
            from = moment(datetimepickerElement.val().split(' - ')[0], 'YYYY-MM-DD');
            if (typeof datetimepickerElement.val().split(' - ')[1] !== 'undefined') {
                to = moment(datetimepickerElement.val().split(' - ')[1], 'YYYY-MM-DD');
            }
        }

        datetimepickerElement.daterangepicker({
            singleDatePicker: false,
            timePicker: false,
            autoApply: true,
            autoUpdateInput:false,
            startDate: from,
            endDate: to,
            locale: {
                format: 'Y-MM-DD'
            }
        });

        datetimepickerElement.val(from.format('Y-MM-DD') + ' - ' + to.format('Y-MM-DD'))

        datetimepickerElement.on('cancel.daterangepicker', function(ev, picker) {
            picker.startDate = from;
            picker.endDateDate =  to;
            $('.table-datetimepicker')
                .val(from.format('Y-MM-DD') + ' - ' + to.format('Y-MM-DD'))

            $('body').trigger('filter.used')
        });

        datetimepickerElement.on('apply.daterangepicker', function(ev, picker) {
            $('.table-datetimepicker')
                .val(moment(picker.startDate).format('Y-MM-DD') + ' - ' + moment(picker.endDate).format('Y-MM-DD'))

            $('body').trigger('filter.used')
        });

        $('.table_filter').each(function(key, filter){
            $(filter).on("keyup",
                debounce(function() {
                    $('body').trigger('filter.used')
                }, 500)
            );
        });

        $('body').on('filter.used', function (event) {
            let filterArray = [];
            event.stopPropagation()
            $('.table_filter').map(function (key, filter) {
                let filterName = $(filter).attr('name');
                //NEW: operator
                let filterValue = $(filter).val();

                filterArray.push({columnName: filterName, value: filterValue})
            });

            let filters = {
                filterArray
            }

            $('#' + $('#table-id').val() ).dataTable().api().search(JSON.stringify(filters)).draw()
        })
    });
};
