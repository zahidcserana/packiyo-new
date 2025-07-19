window.BillingRateCheckForDuplicateRates = function () {
    $(document).ready(function() {
        $('form').submit(function( event ) {
            $('button[type=submit]').attr("disabled", true);
        });
    });
};
