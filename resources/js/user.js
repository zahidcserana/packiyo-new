window.User = function () {
    window.datatables.push({
        selector: '#users-table',
        resource: 'users',
        ajax: {
            url: '/user/data-table'
        },
        columns: [
            {
                'non_hiddable': true,
                "orderable": false,
                "class": "text-left",
                "title": "",
                "name": "users.id",
                "data": function (data) {
                    return data.show_edit_button ? '<button class="table-icon-button" type="button" data-id="' + data.id + '" data-toggle="modal" data-target="#edit-user-modal"><i class="picon-edit-filled icon-lg" title="Edit"></i></button>' : '';
                }
            },
            {"title": "Email", "data": "email", "name": "contact_informations.email"},
            {"title": "Name", "data": "name", "name": "contact_informations.name"},
            {"title": "Last Login", "data": "last_login_at", "name": "last_login_at"},
            {"title": "Clients", "data": "clients", "name": "clients"},
            {"title": "Active", "name": "disabled_at",
                "data": function (data) {
                    return data.show_edit_button ? '<label class="toggle m-0 d-flex justify-content-start"><input type="checkbox" class="disable-toggle" value="' + data.id + '" ' + (data.disabled_at ? '' : 'checked') + '><span class="toggle-slider"></span></label>' : '<label class="toggle m-0 d-flex justify-content-start"><input disabled type="checkbox" value="' + data.id + '" ' + (data.disabled_at ? '' : 'checked') + '><span class="toggle-slider"></span></label>';
                }},
            {
                'non_hiddable': true,
                "orderable": false,
                "class": "text-left",
                "title": "",
                "name": "users.id",
                "data": function (data) {
                    return data.show_delete_button ? '<button class="table-icon-button" type="button" data-id="' + data.id + '" data-email="' + data.email + '" data-toggle="modal" data-target="#delete-user-modal"><i class="picon-trash-filled icon-lg" title="Delete"></i></button>' : '';
                }
            },
        ],
    })

    $(document).find('select:not(.custom-select)').select2();

    $('#edit-user-modal').on('show.bs.modal', function (e) {
        $('#edit-user-modal .modal-content').html(`<div class="spinner">
            <img src="../../img/loading.gif">
        </div>`)

        $.ajax({
            type:'GET',
            serverSide: true,
            url:'/user/getEditUserModal/' + $(e.relatedTarget).data('id'),
            success:function(data) {
                $('#edit-user-modal .modal-content').html(data);
            },
            error: function (response) {
                const modal = $('#edit-user-modal');
                appendValidationMessages(modal, response)
            }
        });
    })

    $('#create-user-modal').on('show.bs.modal', function (e) {
        $('#create-user-modal .modal-content').html(`<div class="spinner">
            <img src="../../img/loading.gif">
        </div>`)

        $.ajax({
            type:'GET',
            serverSide: true,
            url:'/user/getCreateUserModal',
            success:function(data) {
                $('#create-user-modal .modal-content').html(data);
            },
            error: function (response) {
                const modal = $('#create-user-modal');
                appendValidationMessages(modal, response)
            }
        });
    })

    const deleteUserModal = $('#delete-user-modal');

    deleteUserModal.on('show.bs.modal', function (e) {
        $('.delete-user-email').text('/ ' + $(e.relatedTarget).data('email'));
        $('.delete-user-button').attr('data-id', $(e.relatedTarget).data('id'));
    });

    deleteUserModal.on('hide.bs.modal', function (e) {
        const checkbox = $('input[name="confirm_user_delete"]');

        if (checkbox.is(':checked')) {
            checkbox.trigger('click');
        }
    });

    $(document).on('change', 'input[name="confirm_user_delete"]', (e) => {
        $('.delete-user-button').toggleClass('bg-logoOrange text-white');
    });

    $(document).on('change', '#create-user-form input[name="is_admin"]', (e) => {
        $('#create-user-form input[name="user_role_id"]').val($(e.target).is(':checked') ? 1 : 2);
    });

    $(document).on('change', '#edit-user-form input[name="is_admin"]', (e) => {
        $('#edit-user-form input[name="user_role_id"]').val($(e.target).is(':checked') ? 1 : 2);
    });

    $(document).on('click', '.delete-user-button', (e) => {
        e.preventDefault();

        const button = $(e.target);
        const id = button.attr('data-id');

        if (!button.hasClass('bg-logoOrange')) {
            return toastr.error('Please approve user delete.');
        }

        if (button.attr('data-loading')) {
            return;
        }

        button.attr('data-loading', 1);

        $.ajax({
            type: 'GET',
            url: '/user/' + id + '/delete',
            success: function (data) {
                $('#delete-user-modal').modal('toggle');
                toastr.success(data.message);
                dtInstances['#users-table'].ajax.reload();
            },
            error: function (response) {
                toastr.error(response.responseJSON.message);
            },
            complete: function () {
                button.removeAttr('data-loading');
            }
        })
    });

    $(document).on('submit', '#edit-user-form', (e) => {
        e.preventDefault();

        const form = $(e.target);

        if (form.attr('data-loading')) {
            return toastr.success(data.message);
        }

        form.attr('data-loading', 1);

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: new FormData(form[0]),
            processData: false,
            contentType: false,
            success: function (data) {
                $('#edit-user-modal').modal('toggle');
                toastr.success(data.message);
                dtInstances['#users-table'].ajax.reload()
            },
            error: function (response) {
                appendValidationMessages(form, response)
            },
            complete: function () {
                form.removeAttr('data-loading');
            }
        })
    });

    $(document).on('submit', '#create-user-form', (e) => {
        e.preventDefault();

        const form = $(e.target);

        if (form.attr('data-loading')) {
            return;
        }

        form.attr('data-loading', 1);

        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: new FormData(form[0]),
            processData: false,
            contentType: false,
            success: function (data) {
                $('#create-user-modal').modal('toggle');
                toastr.success(data.message)
                dtInstances['#users-table'].ajax.reload()
            },
            error: function (response) {
                appendValidationMessages(form, response)
            },
            complete: function () {
                form.removeAttr('data-loading');
            }
        })
    })

    $(document).on('change', '.disable-toggle', (e) => {
        let url = '/user/' + $(e.target).val() + '/disable';

        if ($(e.target).is(':checked')) {
            url = '/user/' + $(e.target).val() + '/enable';
        }

        $.ajax({
            type: 'GET',
            url: url,
            success: function (data) {
                toastr.success(data.message)
            },
        })
    })
}
