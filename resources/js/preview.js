window.Preview = function () {
    $(document).ready(function() {
        Preview.loadPreview = function  (event, url) {
            if (document.getSelection().toString() === '' ) {
                if($('body').hasClass('edited-in-preview')) {
                    event.preventDefault();
                    editablePreviewPendingChangesModal(event.target)
                } else {
                    $.get(url,
                        function(data, status){
                            let row = $('.preview .row').first();
                            row.nextAll().remove();
                            row.after(data);

                            recalculateDataTableWidth();
                            $('body').addClass('show-preview');
                        }
                    );
                }
            }
        }

        $(document).on('click', '.preview-quick-edit', function (event) {
            event.preventDefault();
            let url = $(this).attr('href');

            $.get(url,
                function(data, status){
                    let row = $('.preview .row').first();
                    row.nextAll().remove();
                    row.after(data);
                    recalculateDataTableWidth();

                    $('body').addClass('show-preview');
                    $('.preview').addClass('editable-preview');
                    dateTimePicker();

                    $("[data-toggle='select']").select2();
                    select2routeFixer();

                    $('body').removeClass('edited-in-preview'); // date picker triggers change event
                    loadedPreview();
                }
            );
        });

        $(document).on('click', '.cancel', function (event) {
            event.preventDefault();
            let editablePreviewAlert = '.editablePreviewAlert';

            $.ajax({
                type: "POST",
                url:  $(this).attr('href'),
                success: function(data)
                {
                    $('#' + $(this).attr('data-table_id')).dataTable().api().ajax.reload()
                    $(editablePreviewAlert).removeClass('alert-danger').addClass('alert-success')
                    $(editablePreviewAlert).show().html(data.message)
                    $('.preview').scrollTop(0)

                    setTimeout(function() {$('.editablePreviewAlert').hide()
                    }, 2000)
                },
                error: function(xhr, status, data)
                {
                    let errorsObject = xhr.responseJSON.errors;

                    $(editablePreviewAlert).removeClass('alert-success').addClass('alert-danger')
                    $(editablePreviewAlert).show().html(Object.values(errorsObject).map(function (a){return a + '<br>'}))
                    $('.preview').scrollTop(0)
                }
            })
        });

        $(document).on('keyup change', '.editable-preview select, .editable-preview input, .editable-preview textarea', function (event) {
            if (!$("body").hasClass("edited-in-preview")) {
                $('body').addClass('edited-in-preview');
            }
        })

        let modalId = '#editable-preview-modal'

        function editablePreviewPendingChangesModal (clickedOn) {
            $(modalId).modal('show');

            editablePreviewPendingChangesModal.saveChangesModal = function () {
                $('#preview-submit-button').trigger('click', [clickedOn]);
                $(modalId).modal('hide');
            }

            editablePreviewPendingChangesModal.discardChanges = function () {
                $('.preview-close-button').trigger('click');
                $(modalId).modal('hide');

                if($(clickedOn).attr('href')) {
                    window.location = $(clickedOn).attr('href');
                } else {
                    $(clickedOn).trigger('click')
                }
            }
        }

        $(document).on('click','a', function(event){
            if($('body').hasClass('edited-in-preview')) {
                event.preventDefault()
                editablePreviewPendingChangesModal(event.target)
            } else {
                return true
            }
        })

        $(modalId + ' .save').on('click', function () {
            editablePreviewPendingChangesModal.saveChangesModal();
        })

        $(modalId + ' .discard-changes').on('click', function () {
            editablePreviewPendingChangesModal.discardChanges();
        })

        $(document).on('click', '.preview-close-button', function () {
            recalculateDataTableWidth();
            $('body').removeClass('show-preview editable-preview edited-in-preview');
            $('.preview').removeClass('editable-preview');
        });

        $(document).on('click', '#preview-submit-button', function (event, data) {
            event.preventDefault();
            let previewForm = $('#preview-form');
            let table = $(this).attr('data-table_id');
            let clickedOn = data;
            let editablePreviewAlert = '.editablePreviewAlert';

            $.ajax({
                type: "PUT",
                headers: {
                    'X-CSRF-TOKEN':  $('meta[name="csrf-token"]').attr('content')
                },
                data: previewForm.serialize(),
                url:  previewForm.attr('action'),
                success: function(data)
                {

                    $('body').removeClass('edited-in-preview');

                    $('#' + table).dataTable().api().ajax.reload()
                    $(editablePreviewAlert).removeClass('alert-danger').addClass('alert-success')
                    $(editablePreviewAlert).show().html(data.message)
                    $('.preview').scrollTop(0)

                    if($(clickedOn).attr('href')) {
                        window.location = $(clickedOn).attr('href');
                    } else {
                        $(clickedOn).trigger('click')
                    }

                    setTimeout(function() {$('.editablePreviewAlert').hide()
                    }, 2000)
                },
                error: function(xhr, status, data)
                {
                    let errorsObject = xhr.responseJSON.errors;

                    $(editablePreviewAlert).removeClass('alert-success').addClass('alert-danger')
                    $(editablePreviewAlert).show().html(Object.values(errorsObject).map(function (a){return a + '<br>'}))
                    $('.preview').scrollTop(0)
                }
            })
        });

        function checkShipping() {
            let carrierId = $('input[name="shipping_carrier_id"]').val();
            let methodId = $('input[name="shipping_method_id"]').val();
            let select = $('.shipping-methods-dropdown select');

            select.val(carrierId + methodId);
        }

        $(document).on('change', '.shipping-methods-dropdown select', function (){
            let option = $(this).find("option:selected");
            let carrierId = option.attr('data-carrier-id');
            let methodId = option.attr('data-method-id');
            let price = option.attr('data-price');

            $('input[name="shipping_carrier_id"]').val(carrierId);
            $('input[name="shipping_method_id"]').val(methodId);
            $('input[name="shipping_price"]').val(price);
        });

        function loadedPreview() {
            let customerIdField = $('.customer_id');
            if (customerIdField.val()) {
                $.get("/orders/shipping_methods/" + customerIdField.val(), { type: "dropdown" }, function(data, status){
                    $('.shipping-methods-dropdown').html(data);
                    checkShipping();
                });
            }

            if(!$('#fill-information').is(':checked')) {
                $('.billing_contact_information').hide();
            }
        }

        $(document).on('click', '#fill-information', function () {
            if ($(this)[0].checked) {
                $('.billing_contact_information').show();
                $('.sizing').addClass('col-xl-6');
            } else {
                $('.billing_contact_information').hide();
                $('.sizing').removeClass('col-xl-6');
            }
        });
    })
}
