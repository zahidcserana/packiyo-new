window.ShippingBoxTable = function () {
    $(document).ready(function () {
        function showSelection(show) {
            let items = $('.show-or-not');
            if (show) {
                items.show()
            } else {
                items.hide()
            }
        }

        $('.show-or-not-check').on('change', function () {
            let show = false;

            if ($(this).is('input[type="checkbox"]')) {
                show = !($(this).val() === true && $(this).is(':checked'));
            }

            if ($(this).is('input[type="radio"]')) {
                show = $(this).val() === true;
            }
            showSelection(show)
        })

        $('.show-or-not-check:checked').trigger('change')

        function checkIfItemLineIslast() {
            let itemTo = $('.item_to');

            itemTo.prop('readonly', false);
            itemTo.not(':last').prop('readonly', true);

            let removeItemCol = $('.remove-item-col');

            removeItemCol.removeClass('d-none').addClass('d-flex');

            if (removeItemCol.length > 1) {
                removeItemCol.not(':last').removeClass('d-flex').addClass('d-none')
            }
        }

        checkIfItemLineIslast();

        $(document).on('click', '.remove-item', function (event) {
            $(this).closest('tr').remove();
            checkIfItemLineIslast()
            event.preventDefault();
        });

        let selectablesTables = $('.selectables-table')

        selectablesTables.each(function () {
            if ($(this).attr('data-url')) {
                let selectablesInput = $('.' + $(this).attr('data-selectables'));
                let subSelectablesInput = $('.' + $(this).attr('data-sub-selectables'));
                let selectablesUrl = $(this).attr('data-url')
                let selectablesTable = $(this)
                let selectables = selectablesInput.val() ?
                    JSON.parse(selectablesInput.val()) : '[]'

                let subSelectables = subSelectablesInput.val() ?
                    JSON.parse(subSelectablesInput.val()) : {}

                let selectablesColumns = [
                    {
                        "title": "",
                        "data": function (data) {
                            let isChecked = selectables.includes(String(data.id)) ? 'checked' : ''
                            return (
                                "<input " +
                                'class="item-checkbox" ' +
                                'type="checkbox" ' +
                                "" +
                                isChecked +
                                " " +
                                'value="' +
                                data.id +
                                '" ' +
                                "/>"
                            );
                        },
                        "name": ""
                    },
                    {
                        "title": "Name",
                        "data": function (data) {
                            let name = data.name;
                            let subSelectablesCheckbox = '';

                            if (data.subSelectables) {
                                let count = '0';
                                if (subSelectables[data.id]) {
                                    count = subSelectables[data.id].length
                                }

                                let totalCount = Object.keys(data.subSelectables).length

                                subSelectablesCheckbox =
                                    '<p class="manage-subselectables"><span class="count">' + count + '</span>\/<span class="totalCount">' + totalCount + '</span></p>'
                            }

                            return name + subSelectablesCheckbox
                        },
                        "name": "",
                    },
                ];

                selectablesTable.DataTable(
                    {
                        serverSide: true,
                        ajax: selectablesUrl,
                        responsive: true,
                        pagingType: "simple",
                        scrollX: true,
                        pageLength: 10,
                        sDom: '<"top">rt<"bottom"<"col col-12"p>>',
                        "language": {
                            "paginate": {
                                "previous": "<",
                                "next": ">"
                            }
                        },
                        columns: selectablesColumns,

                        createdRow: function (row, data, dataIndex) {
                            $(row).find('.item-checkbox').on('click', function (event) {
                                let subSelectablesIds = []

                                if (data.subSelectables) {
                                    $(data.subSelectables).each(function () {
                                        subSelectablesIds.push(String(this.id));
                                    })
                                }

                                if ($(this).is(':checked')) {
                                    if (!selectables.includes($(this).val())) {
                                        selectables.push($(this).val())
                                    }

                                    subSelectables[data.id] = subSelectablesIds
                                } else {
                                    if (selectables.includes($(this).val())) {
                                        selectables.splice(selectables.indexOf($(this).val()), 1)
                                    }
                                    subSelectables[data.id] = []
                                }
                                selectablesInput.val(JSON.stringify(selectables))

                                if (data.subSelectables) {
                                    subSelectablesInput.val(JSON.stringify(subSelectables))
                                }

                                selectablesTable.DataTable().draw(false)
                            });
                            $(row).find('.manage-subselectables').on('click', function () {

                                $.ajax({
                                    method: 'GET',
                                    url: '/billing_rates/' + $(row).find('.item-checkbox').val() + '/shipping_boxes/',
                                    success: function (results) {
                                        $('#selectables-modal .modal-body').replaceWith(results)
                                        $('#selectables-modal').modal('show')

                                        $('#selectables-modal .modal-body .sub-selection-checkbox').each(function () {
                                            let carrierId = $(this).attr('data-carrier-id');
                                            let inputValue = $(this).val()

                                            if (subSelectables[carrierId]) {
                                                if (subSelectables[carrierId].includes(inputValue)) {
                                                    $(this).prop('checked', true)
                                                }
                                            }
                                        })

                                        $('#selectables-modal .modal-body').find('.sub-selection-checkbox').on('change', function () {
                                            let carrierId = $(this).attr('data-carrier-id')
                                            let checkboxValue = $(this).val()

                                            if ($(this).is(':checked')) {
                                                if (subSelectables.hasOwnProperty(carrierId)) {
                                                    if (!subSelectables[carrierId].includes(checkboxValue)) {
                                                        subSelectables[carrierId].push(checkboxValue)
                                                    }
                                                } else {
                                                    subSelectables[carrierId] = [checkboxValue]
                                                }
                                            } else {
                                                let valuesIndex = subSelectables[carrierId].indexOf(checkboxValue)

                                                subSelectables[carrierId].splice(valuesIndex, 1)
                                            }

                                            subSelectablesInput.val(JSON.stringify(subSelectables))
                                            selectablesTable.DataTable().draw()
                                        });
                                    },
                                    error: function (xhr) {
                                        console.log(xhr.responseJSON.message, false)
                                    }
                                })
                            });
                        },
                    });

                selectablesTable.on('draw.dt', function () {
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
                    })

                    $('#selectables-modal .modal-body .sub-selection-checkbox').each(function () {
                        if ($(this).is(':checked')) {
                            return false;
                        }
                    })
                })
            }
        })
    });
};
