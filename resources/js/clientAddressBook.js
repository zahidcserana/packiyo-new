window.AddressBook = function () {
    if ($('#address-books-table').length) {
        const filterForm = $('#toggleFilterForm').find('form')
        window.loadFilterFromQuery(filterForm)

        window.datatables.push({
            selector: '#address-books-table',
            resource: 'address-books',
            ajax: {
                url: '/address_book/data-table',
                data: function (data) {
                    let request = window.serializeFilterForm(filterForm)

                    data.filter_form = request

                    window.queryUrl(request)
                }
            },
            columns: [
                {
                    "title": "",
                    "data": function (data) {
                        return `<a href="#" data-id="${data.id}" data-toggle="modal" data-target="#address-book-modal"><i class="picon-edit-filled icon-lg" title="Edit"></i></a>`
                    },
                    "orderable": false,
                    "name": "address_books.id"
                },
                { "title": "Address book name", "data": "address_book_name", "name": "address_books.name" },
                { "title": "Address name", "data": "name", "name": "contact_informations.name" },
                { "title": "Address", "data": "address", "name": "contact_informations.address" },
                { "title": "City", "data": "city", "name": "contact_informations.city" },
                { "title": "State / Province", "data": "state", "name": "contact_informations.state" },
                { "title": "Zip / Postal code", "data": "zip", "name": "contact_informations.zip" },
                { "title": "Country", "data": "country", "name": "countries.iso_3166_2" },
                {
                    'non_hiddable': true,
                    'orderable': false,
                    'class': 'text-center',
                    'title': '',
                    'data': function (data) {
                        return app.tableDeleteButton(
                            `Are you sure you want to delete ${data.name}?`,
                            data.link_delete
                        );
                    }
                }
            ]
        })
    }

    $('#address-book-modal').on('show.bs.modal', function (e) {
        let itemId = $(e.relatedTarget).data('id');

        if (typeof itemId == 'undefined') {
            itemId = ''
        }

        $('#address-book-modal .modal-content').html(`<div class="spinner">
            <img src="../../img/loading.gif">
        </div>`)

        $.ajax({
            type: 'GET',
            serverSide: true,
            url: '/address_book/modal/' + itemId,
            success: function (data) {
                $('#address-book-modal > div')
                    .html(data)
                    .find('select').select2()
            },
            error: function (response) {
                appendValidationMessages($('#address-book-modal'), response)
            }
        })
    })
};
