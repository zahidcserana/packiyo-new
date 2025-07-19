<div class="modal fade" id="invoice-recalculate-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="modal-form-invoice-recalculate" method="POST">
                @csrf
                <div class="modal-header px-0">
                    <div class="mx-4 pb-4 d-flex w-100 border-bottom-gray">
                        <h6 class="modal-title text-black text-left" id="modal-title-notification">
                            <span id="title-modal"></span>
                        </h6>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true" class="text-black">&times;</span>
                        </button>
                    </div>
                </div>

                <div class="modal-body py-0">
                    {{ __('The existing invoice will be archived, and a new invoice will be calculated using the billing card currently assigned to client ')}}
                    <b> {{$customer->contactInformation->name}}</b>
                </div>
                <div class="modal-footer justify-content-center">
                    <button  id="recalculate-submit" type="submit" class="btn btn-primary">{{ __('Recalculate') }}</button>
                    <button type="submit" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
    <script>
        $(document).ready(function () {
            // Pass data to the modal
            $('#invoice-recalculate-modal').on('show.bs.modal', function (event) {
                let button = $(event.relatedTarget); // Button that triggered the modal
                let invoiceId = button.data('invoice-id'); // Extract info from data-* attributes
                let periodStart = button.data('period-start');
                let periodEnd = button.data('period-end');
                let invoiceNumber = button.data('invoice-number'); // Extract info from data-* attributes

                let titleText = `Recalculate the invoice ${invoiceNumber !== 'Not set' ? '#'+invoiceNumber :''} for period ${periodStart} to ${periodEnd}`;
                $('#title-modal').text(titleText);

                let form = $('#modal-form-invoice-recalculate');
                let formAction = '/invoices/' + invoiceId + '/recalculate'; // Define your action URL structure
                form.attr('action', formAction);
            }).submit(function(event) {
                let btn = $('#recalculate-submit')
                btn.prop('disabled', true)
            });
        })
    </script>
@endpush
