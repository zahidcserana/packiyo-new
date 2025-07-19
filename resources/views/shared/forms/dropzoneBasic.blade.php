<div class="dropzone-container {{ $dropzoneContainerClass ?? '' }}">
    <div
        id="{{ $name }}"
        data-multiple="{{ $isMultiple ?? 0 }}"
        data-url="{{ $url }}"
        action="{{ $url }}"
        data-images="{{ $images ?? '' }}"
        class="dropzone dropzone-multiple p-0 dropzone-body"
    >
        <div class="dz-message" data-dz-message>
            <button type="button" class="upload-image-button d-flex">
                <i class="picon-upload-light icon-lg icon-black mr-2"></i>
                {{ __('Click or drop image') }}
            </button>
        </div>
        <div class='fallback'>
            <input name='{{ $name }}' type='file'/>
        </div>
    </div>

    <div class="previews {{ $name }}-container"></div>
</div>
