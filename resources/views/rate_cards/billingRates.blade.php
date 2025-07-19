
<div class="row">
    <div class="col col-12">
        <div class="card bg-transparent shadow-none">
            <div class="nav-wrapper p-0">
                <ul class="nav nav-pills nav-fill flex-md-row nav-bg-gray">
                    @foreach(\App\Models\BillingRate::BILLING_RATE_TYPES as $type => $info)
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0 {{$loop->first ? 'active' : ''}}" id="{{$type}}-tab" data-toggle="tab" href="#{{$type}}" role="tab" aria-controls="{{$type}}">
                                {{__($info['title'])}}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col col-12 p-0">
        <div class="row">
            <div class="col col-12 p-0">
                <div class="tab-content" id="myTabContent">
                    @foreach(\App\Models\BillingRate::BILLING_RATE_TYPES as $type => $info)
                        <div class="tab-pane fade show {{$loop->first ? 'active' : ''}}" id="{{$type}}" role="tabpanel" aria-labelledby="{{$type}}-tab">
                            <x-datatable
                                search-placeholder="{{ __('Search Rate') }}"
                                table-id="{{$type}}_table"
                                :data="$data"
                                datatableOrder="{!! json_encode($datatableOrder) !!}"
                                tableContainerClass="p-0 slim-table"
                                tableClass="billing-rates-table p-0 pb-5"
                            >
                            </x-datatable>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        const rateCardId = {{$rateCard->id}};
        new BillingRate();
    </script>
@endpush

