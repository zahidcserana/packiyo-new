window.Easypost = (carrierCredentials) => {
    const clientCredentialsDiv = $('.client-credentials')

    $(document).ready(function () {
        updateCarrierForm()
    })

    $('#input-type').on('change', function () {
        clientCredentialsDiv.empty()
        updateCarrierForm()
    })

    function updateCarrierForm() {
        const carrier = carrierCredentials[$("#input-type").find(":selected").val()];

        if (carrier) {
            let credentials = carrier['credentials'];

            Object.entries(credentials).forEach(function (element) {
                if (element[1].visibility === "checkbox") {
                    let input =
                        '<div class="custom-form-checkbox my-4">' +
                        '<input type="hidden" name="credentials[' +
                        element[0] +
                        ']" value="0">' +
                        '<input class="" name="credentials[' +
                        element[0] +
                        ']" id="chk-' +
                        element[0] +
                        '" type="checkbox" checked="" value="1">' +
                        '<label class="text-black font-weight-600" for="chk-' +
                        element[0] +
                        '">' +
                        element[1].label +
                        "</label>" +
                        "</div>";

                    clientCredentialsDiv.append(input);
                } else {
                    let label = $(
                        '<label class="form-control-label text-neutral-text-gray font-weight-600 font-xs">' +
                            element[1].label +
                            "</label>"
                    );
                    let input = $(
                        '<input class="p-2 form-control font-sm h-auto" name="credentials[' +
                            element[0] +
                            ']">'
                    );

                    clientCredentialsDiv.append(label);
                    clientCredentialsDiv.append(input);
                }
            });

            if (carrier.hasOwnProperty('test_credentials')) {
                $('.client-test-credentials').show()

                let clientTestCredentialsDiv = $('#test-credentials')
                clientTestCredentialsDiv.empty()
                let testCredentials = carrier['test_credentials']

                Object.entries(testCredentials).forEach(function (element) {
                    if (element[1].visibility === "checkbox") {
                        let input =
                            '<div class="custom-form-checkbox my-4">' +
                            '<input type="hidden" name="test_credentials[' +
                            element[0] +
                            ']" value="0">' +
                            '<input class="" name="test_credentials[' +
                            element[0] +
                            ']" id="chk-' +
                            element[0] +
                            '" type="checkbox" checked="" value="1">' +
                            '<label class="text-black font-weight-600" for="chk-' +
                            element[0] +
                            '">' +
                            element[1].label +
                            "</label>" +
                            "</div>";

                        clientTestCredentialsDiv.append(input);
                    } else {
                        let label = $(
                            '<label class="form-control-label text-neutral-text-gray font-weight-600 font-xs">' +
                            element[1].label +
                            "</label>"
                        );
                        let input = $(
                            '<input class="p-2 form-control font-sm h-auto" name="test_credentials[' +
                            element[0] +
                            ']">'
                        );

                        clientTestCredentialsDiv.append(label);
                        clientTestCredentialsDiv.append(input);
                    }
                });
            } else {
                $('.client-test-credentials').hide()
                $('#test-credentials').empty()
            }
        }
    }
}
