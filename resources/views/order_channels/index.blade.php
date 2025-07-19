@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Connections',
        'buttons' => [
            [
                'title' => __('+'),
                'href' => '#',
                'data-toggle' => 'modal',
                'data-target' => '#orderChannelListModal',
                'className' => 'add-order-channel-btn'
            ]
        ]
    ])

    <div class="container-fluid">
        <div class="row">
            @foreach($orderChannels as $orderChannel)
                <div class="col-4 col-sm-3 col-md-2 {{ $orderChannel->is_disabled ? 'disabled-look-with-clickable' : '' }}">
                    <a href="{{ route('order_channels.getOrderChannel', [ 'orderChannel' => $orderChannel ]) }}">
                        <div class="card">
                            <img class="card-img-top" src="{{ \Illuminate\Support\Str::startsWith($orderChannel->image_url, config('tribird.base_url')) ? $orderChannel->image_url : config('tribird.base_url') . $orderChannel->image_url}}" width="100" alt="{{$orderChannel->name ?? ''}} image">
                            <div class="card-body text-center py-0">
                                <h5 class="card-title">{{$orderChannel->name ?? ''}}</h5>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    @include('shared.modals.orderChannelList')
@endsection

@push('js')
    <script>
        new OrderChannel()
    </script>
@endpush
