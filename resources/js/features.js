window.FeaturesForm = function () {
    $(document).ready(function () {
        const changeLoginLogoButton = $(".change-login-logo-button");
        const deleteLoginLogoButton = $(".delete-login-logo-button");

        changeLoginLogoButton.click(function(e) {
            e.preventDefault();
            $('.login-logo-input').trigger('click');
        });

        deleteLoginLogoButton.click(function(e) {
            e.preventDefault();
            $('input[name="App\Features\LoginLogo"]').val('');
            deleteLoginLogoButton.after('<input hidden name="delete_login_logo" value="1"/>');
            const logoPreview = $('.login-logo-preview');
            const defaultSrc = logoPreview.attr('data-default-src');
            logoPreview.attr('src', defaultSrc);
            toggleButtonsClasses();
        });

        function toggleButtonsClasses () {
            changeLoginLogoButton.toggleClass('d-flex d-none');
            deleteLoginLogoButton.toggleClass('d-flex d-none');
        }

        function previewLoginLogo (input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    $(".login-logo-preview").attr("src", e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        }

        $(".login-logo-input").change(function(){
            previewLoginLogo(this);
        });
    });
};
