@props([
    'title' => '',
    'subtitle' => '',
    'tooltip' => '',
    'autoSave' => false,
    'containerClass' => '',
    'class' => 'col-12',
])

<div class="base-ajax-section {{ $autoSave ? 'auto-save-section' : '' }} {{ $class }}">
    <div class="bg-white border-12 pb-2">
        <div class="p-4 pb-0 d-flex align-items-center">
            <h6 class="modal-title text-black text-left">
                {{ $title }}
            </h6>

            @if ($subtitle)
                <span class="ml-2 modal-sub-title">
                    {{ $subtitle }}
                </span>
            @endif

            @if ($tooltip)
                <i class="picon-alert-circled-light ml-auto" data-toggle="tooltip" data-placement="top" data-html="true" title="{{ $tooltip }}"></i>
            @endif
        </div>

        <div class="{{ $containerClass }}">
            {{ $slot }}
        </div>
    </div>
</div>
