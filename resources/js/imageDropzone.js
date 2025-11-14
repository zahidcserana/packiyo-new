window.ImageDropzone = function (formId = '', buttonId = '') {
    let uploadedImages = []
    let dropzoneIds = []
    let myDropzones = []

    $('.dropzone-body').each(function () {
        dropzoneIds.push(this.id);
    });

    dropzoneIds.forEach(function (dropzoneId) {
        Dropzone.autoDiscover = false;

        let dzb = $('#' + dropzoneId)
        const isMultiple = dzb.data('multiple');
        const images = dzb.data('images');
        let imageFile = [];

        dzb.dropzone({
            autoProcessQueue: false,
            uploadMultiple: isMultiple,
            addRemoveLinks: true,
            dictRemoveFile: '<div class="deleteBtn"><i class="fas fa-trash-alt text-lightGrey"></i></div>',
            parallelUploads: isMultiple ? 10 : 1,
            acceptedFiles: ".jpeg,.jpg,.png,.gif",
            maxFiles: isMultiple ? 10 : 1,
            maxFilesize: 8192,
            previewsContainer: '.' + dropzoneId + '-container',
            url: dzb.attr("data-url"),
            maxfilesexceeded: function(file) {
                this.removeAllFiles();
                this.addFile(file);
            },
            init: function () {
                const myDropzone = this;
                window.myDropzone = this;
                myDropzones[dropzoneId] = this

                myDropzone.on('addedfile', function (file) {
                    if (!file.id) {
                        if (isMultiple) {
                            imageFile.push(file)
                            uploadedImages[dropzoneId] = imageFile
                        } else {
                            uploadedImages[dropzoneId] = file
                        }
                    }
                });

                if (images) {
                    if (images.id) {
                        addImage(myDropzone, images);
                    } else {
                        for (let i = 0; i < images.length; i++) {
                            addImage(myDropzone, images[i]);
                        }
                    }
                }
            },
            removedfile: function (file, response) {
                if (file.id) {
                    $.get('/image_delete/' + file.id, function(data, status) {
                        if(data.success){
                            file.previewElement.remove();
                            $(document).find('img[src="' + file.source + '"].detailsImage').remove()
                        } else {
                            alert('Error, try to restart page.');
                        }
                    });
                } else {
                    file.previewElement.remove();
                }
            }
        });
    })

    function addImage(myDropzone, parsedImage) {
        myDropzone.emit('addedfile', parsedImage);
        myDropzone.emit('thumbnail', parsedImage, parsedImage.source);
        myDropzone.emit('"complete', parsedImage);
    }

    $(document).on('click', "#" + buttonId, function (e) {
        e.preventDefault();

        window.updateCkEditorElements()

        const _form = $('#' + formId);
        const form = _form[0];
        let formData = new FormData(form);

        if (!$.isEmptyObject(uploadedImages)) {
            dropzoneIds.forEach(function (dropzoneId) {
                if ($('#' + dropzoneId).data('multiple') && uploadedImages[dropzoneId] !== undefined) {
                        uploadedImages[dropzoneId].forEach(function (file) {
                        formData.append(dropzoneId + "[]", file)
                    })
                } else {
                    formData.append(dropzoneId, uploadedImages[dropzoneId])
                }
            })
        }

        $.post({
            enctype: 'multipart/form-data',
            headers: {'X-CSRF-TOKEN': formData.get('_token')},
            url: _form.attr('action'),
            data: formData,
            async: false,
            processData: false,
            contentType: false,
            success: function (data) {
                toastr.success(data.message)
                resetContainer(_form, data)
                uploadedImages = []
            },
            error: function (messages) {
                $.map(messages.responseJSON.errors, function (message) {
                    if (Array.isArray(message)) {
                        $.each(message, function (k, v) {
                            toastr.error(v)
                        })
                    } else {
                        toastr.error(message)
                    }
                })
            }
        });
    })

    function resetContainer(_form, data) {
        if (_form.attr('id') == 'product-form') {
            _form.find('.loading').addClass('d-none')
            _form.find('.saveSuccess').removeClass('d-none').css('display', 'block').fadeOut(5000)
            _form.removeClass('editable')

            let detailsImgCont = $('#detailsImageContainer');
            detailsImgCont.append('<p class="text-center w-100">No images</p>')
            let myDropzone = myDropzones['file']
            myDropzone.removeAllFiles()

            if (data.product.product_images.length) {
                $('.previews').empty()
                detailsImgCont.empty()
                $.each(data.product.product_images, function(key,value) {
                    let mockFile = { name: value.name, size: value.size };
                    $('#detailsImageContainer').append('<img class="detailsImage mr-2" src="' + value.source + '">')
                    myDropzone.emit("addedfile", mockFile);
                    myDropzone.emit("thumbnail", mockFile, value.source);
                    myDropzone.emit("complete", mockFile);
                });
            }

            reloadAuditLog()
        }

        if (_form.attr('id') == 'product-create-form') {
            let modal = $('#productCreateModal');
            modal.modal('toggle');
            modal.find('form').trigger("reset");
            modal.find('.nav-link.text-danger').removeClass('text-danger');
        }
    }
};
