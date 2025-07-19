window.ColumnVisibilityBeforeTableLoad = function (columns) {
    if(typeof hideColumns !== 'undefined') {
        columns.forEach(function (value, number) {
            hideColumns.includes((value.name).toString()) ? value.visible = false : true
        })
    }
};

window.ShowHideColumnInitComplete = function (tableId, ajaxUrl, settingsTableKey) {
    let dtable = $(tableId).dataTable().api();
    dtable.columns()[0].map(function (column) {
        let title = dtable.column(column).header().innerText.trim();

        if (title) {
            let columnName = dtable.init().columns[column].name;
            let modal = $('#table-column-show-hide-modal');
            let isChecked = JSON.parse(hideColumns).includes(columnName) ? '' : 'checked';
            let checkBox =
                '<li class="list-group-item p-2">' +
                '<div class="custom-control custom-checkbox">'+
                '<input id="column-'+column+'" data-column="'+column+'" type="checkbox" class="column_hide_show_checkbox custom-control-input" name="hide_columns[]" value="'+columnName+'" '+isChecked+'>' +
                '<label class="custom-control-label" for="column-'+column+'">'+title+'</label>' +
                '</div>'+
                '</li>'

            modal.find('#table-column-name-list').append(checkBox)
        }
    })

    $('#table-column-show-hide-modal .column_hide_show_checkbox').on('click', function (e) {
        const column = dtable.column($(this).attr("data-column"));
        column.visible($(this).is(":checked"));
    } );

    $('#column_show_save').on('click', function () {
        let key = settingsTableKey;
        let columnsUnchecked = $('#table-column-show-hide-modal #table-column-name-list .column_hide_show_checkbox:not(:checked)');
        let columnNames =[];

        columnsUnchecked.map( function (key, item) {
            columnNames.push($(item).val());
        })

        let data = {};

        data[key] = JSON.stringify(columnNames);

        $.ajax({
            method:'POST',
            url: ajaxUrl,
            data: data
        })
    })
};
