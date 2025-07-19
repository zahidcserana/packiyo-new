<div class="modal-content" id="create-edit-content">
    <div class="modal-header border-bottom mx-4 px-0">
        <h6 class="modal-title text-black text-left">
            @if ($returnStatus)
                {{ __('Edit Return Status') }}: {{ $returnStatus->name }}
            @else
                {{ __('Create Return Status') }}
            @endif
        </h6>

        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
            <span aria-hidden="true" class="text-black">&times;</span>
        </button>
    </div>

    <div class="modal-body">
        <form
            method="POST"
            @if ($returnStatus)
                action="{{ route('return_status.update', ['return_status' => $returnStatus]) }}"
            @else
                action="{{ route('return_status.store') }}"
            @endif
            autocomplete="off"
            id="create-edit-form"
        >
            @csrf
            @if ($returnStatus)
                @method('PUT')
            @endif

            @include('return_status.returnStatusInformationFields', [
                '$returnStatus' => $returnStatus
            ])

            <div class="text-center">
                <button type="submit" class="btn bg-logoOrange text-white mx-auto px-5 font-weight-700">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
