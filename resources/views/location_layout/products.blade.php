@extends('layouts.location_layout')

@section('content')
    <div class="row">
        @foreach($locationProducts as $locationProduct)
            <div class="col py-2 border">
                <a href="{{ route('product.edit', ['product' => $locationProduct]) }}" target="_blank">
                    @if ($locationProduct->pivot->product->productImages->first())
                        <img class="mw-100" src="{{ $locationProduct->pivot->product->productImages->first()->source }}" /><br />
                    @endif
                    {{ $locationProduct->product->sku }}<br />
                    {{ $locationProduct->product->name }}<br />
                    {{ __('On hand:') }} {{ $locationProduct->pivot->quantity_on_hand }}<br />
                    {{ __('Reserved for picking:') }} {{ $locationProduct->pivot->quantity_reserved_for_picking }}<br />
                    <img src="data:image/png;base64,{{
                    base64_encode(
                        app(Picqer\Barcode\BarcodeGeneratorPNG::class)
                            ->getBarcode($locationProduct->pivot->product->barcode, \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128)
                    )
                }}"><br />
                    {{ $locationProduct->pivot->product->barcode }}
                </a>
            </div>
        @endforeach
    </div>
@endsection
