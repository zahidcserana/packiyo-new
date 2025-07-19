@extends('layouts.app')

@section('content')
    @include('layouts.headers.auth', [
        'title' => 'Connections',
        'subtitle' => $orderChannel->name ?? '',
        'buttons' => [
            [
                'title' =>  $orderChannel->is_disabled ? 'Enable' : 'Disable' ,
                'href' => '#',
                'className' => 'enable-disable-order-channel-btn',
                'data-toggle' => 'modal',
                'data-target' => '#confirm-modal'
            ]
        ]
    ])
    <input type="hidden" id="channelId" value="{{ $orderChannel->id ?? '' }}">

    <div class="container-fluid py-3 position-relative h-lg-100  {{ $orderChannel->is_disabled ? 'disabled-look-without-clickable' : '' }}">
        <div class="row">
            <div class="col-6">
                @include('order_channels.syncs')
                @include('order_channels.integrationConfigurations')
                @include('order_channels.configurations')
            </div>
            <div class="col">
                @include('order_channels.schedulers')
                @include('order_channels.webhooks')
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script>
        new OrderChannel()
    </script>
@endpush
