window.DataTableAddons = function () {
    $(document).ready(function() {
        let from = moment().subtract(14, 'd')
        let to = moment().add(1,'days')

        $('.table-datetimepicker').daterangepicker({
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

        $('.table-datetimepicker').val(from.format('Y-MM-DD') + ' - ' + to.format('Y-MM-DD'))

        $('.table-datetimepicker').on('cancel.daterangepicker', function(ev, picker) {
            picker.startDate = from;
            picker.endDateDate =  to;
            $('.table-datetimepicker')
                .val(from.format('Y-MM-DD') + ' - ' + to.format('Y-MM-DD'))

            $('body').trigger('filter.used')
        });

        $('.table-datetimepicker').on('apply.daterangepicker', function(ev, picker) {
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

        $('#' + $('#table-id').val() ).on('order.dt', function (event, settings, ordArr ) {
            $('.ordering').val(   $('#' + $('#table-id').val() ).DataTable().init().columns[ordArr[0].col].name + ',' + ordArr[0].dir)
        })

        $('.ordering option').map(function (num, option) {
            let selectVal = $(option).val().split(',')
            let columnName = selectVal[0]
            let columnDir = selectVal[1]

            $('#' + $('#table-id').val() ).DataTable().init().columns.forEach(function(column, key) {
                if(column.name == columnName) {
                    $(option).attr('data-column-index', key)
                    $(option).attr('data-column-dir', columnDir)
                }
            })
        })

        $('.ordering').on('change', function () {
            let selectedOption = $('.ordering option:selected')

            $('#' + $('#table-id').val() ).DataTable().order([
                selectedOption.attr('data-column-index'),
                selectedOption.attr('data-column-dir')
            ]).draw()

        })
    });
};
