window.RateCard = function () {
    window.datatables.push({
        selector: '#rate-cards-table',
        resource: 'rate-cards',
        ajax: {
            url: '/rate_cards/data_table'
        },
        columns: [
            {
                "title": "",
                "data": function (data) {
                    return '<a href="' + data['link_edit'] + '" class="table-icon-button" type="button"><i class="picon-edit-filled icon-lg" title="Edit"></i></a>';
                },
                "orderable": false,
                "name": "rate_cards.id"
            },
            {
                "title": "Name",
                "data": "name",
                "name": "rate_cards.name"
            },
            {
                "title": "Last Modified",
                "data": "updated_at",
                "name": "rate_cards.updated_at"
            },
            {
                "orderable": false,
                "class":"text-center",
                "title": "Actions",
                "data": function (data) {
                    const cloneButton = app.tableCloneButton(
                        `Duplicate selected rate card?`,
                        data.link_clone
                    );

                    const deleteButton = app.tableDeleteButton(
                        `Delete selected rate card?`,
                        data.link_delete
                    );

                    return cloneButton + deleteButton
                },
            },
        ],
        createdRow: function( row, data, dataIndex ) {
            $(row).attr( 'data-edit-link', data['link_edit'] );
        },
    })
};
