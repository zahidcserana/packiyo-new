<div class="modal-body">
    <ul class="list-group mb-3">
        @foreach($methodList as $item)
            <li class="list-group-item">
                <input type="checkbox" id="{{'shipping-box-' . $item->id}}" class="sub-selection-checkbox" data-carrier-id="{{$customerId}}" value="{{$item->id}}">
                <label class="form-check-label" for="{{'shipping-box-' . $item->id}}">
                    {{$item->name}}
                </label>
            </li>
        @endforeach
    </ul>
</div>
