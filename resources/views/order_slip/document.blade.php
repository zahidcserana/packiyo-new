<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ sprintf("%011d", $order->id) }}_order_slip.pdf</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

        <style type="text/css" media="screen">
            * {
                line-height: 1.1;
            }

            html {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
                line-height: 1.15;
                margin: 0;
            }

            body {
                font-weight: 400;
                line-height: 1.5;
                color: #212529;
                text-align: left;
                background-color: #fff;
                font-size: 9pt;
                margin: 10pt;
                margin-bottom: 50pt;
            }

            h4 {
                margin-bottom: 0.5rem;
                line-height: 1.2;
                margin-top: 0;
                font-weight: 700;
                text-decoration: none;
                vertical-align: baseline;
                font-size: 12pt;
                font-style: normal;
            }

            p {
                margin-top: 0;
                margin-bottom: 1rem;
            }

            strong {
                font-weight: bolder;
            }

            img {
                vertical-align: middle;
                border-style: none;
            }

            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                color: #000;
                background-color: #fff;
                margin: 0 33pt;
                height: {{ $footerHeight == 0.0 ? `auto`: $footerHeight . `pt` }};
            }

            .table.items-table {
                padding-bottom: {{ $footerHeight == 0.0 ? `auto`: $footerHeight . `pt` }};
            }

            table {
                border-collapse: collapse;
            }

            th {
                text-align: inherit;
            }

            .table {
                width: 100%;
                margin-bottom: 1rem;
            }

            .table th,
            .table td {
                padding: 5pt;
                vertical-align: middle;
            }

            .table.items-table td {
                border-top: 1px solid #dee2e6;
                font-size: 9pt;
            }

            .table thead th {
                vertical-align: bottom;
                border-bottom: 2px solid #dee2e6;
            }

            table.info-table td {
                font-weight: 400;
                text-decoration: none;
                vertical-align: baseline;
                font-size: 9pt;
                font-style: normal;
                padding: 0;
            }

            table.items-table th {
                border-top: 1px solid #000;
                border-bottom: 1pt dotted #999;
                font-size: 9pt;
                font-weight: 400;
            }

            table.items-table tr {
                border-bottom: 1pt dotted #999;
            }

            .mt-3 {
                margin-top: 1rem !important;
            }

            .pl-1 {
                padding-left: 1rem !important;
            }

            .text-right {
                text-align: right !important;
            }

            .text-center {
                text-align: center !important;
            }

            .border-0 {
                border: none !important;
            }

            .logo {
                max-height: 80pt;
                max-width: 140pt;
                margin-right: 20pt;
            }

            .slip-textbox {
                page-break-inside: avoid;
                line-height: 1.15;
            }

            .ship-to {
                vertical-align: top !important;
            }

            .info-text {
                padding-left: .25rem !important
            }
        </style>
    </head>

    <body>
        {{-- Add footer to each page --}}
        <footer>
            {!! customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_FOOTER) !!}

            <div class="text-center">
                <img src="data:image/png;base64,{{
                        base64_encode(
                            app(Picqer\Barcode\BarcodeGeneratorPNG::class)
                                ->getBarcode(sprintf("scanid%011d", $order->id), \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128)
                        )
                    }}" alt="barcode">

                <p class="mt-3">{{ __('Order: :number', ['number' => $order->number]) }}</p>
            </div>
        </footer>

        {{-- Shipping info / logo --}}
        <table class="table">
            <tbody>
                <tr>
                    <td class="text-right">
                        <h1>
                            {{ customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_HEADING) }}
                        </h1>
                    </td>
                    <td class="border-0 text-right">
                        <img
                            class="logo"
                            @if ($order->customer->orderSlipLogo)
                                src="{{ Storage::path($order->customer->orderSlipLogo->filename) }}"
                            @endif
                            alt="logo"
                        >
                    </td>
                </tr>
                <tr>
                    <td class="ship-to">
                        <table class="info-table">
                            <tbody>
                                <tr>
                                    <td><strong>{{ __('Ship To:') }}</strong></td>
                                    <td class="info-text">
                                        {{ $order->shippingContactInformation->name ?? '' }}<br />
                                        {{ $order->shippingContactInformation->address ?? '' }}<br />
                                        @if ($order->shippingContactInformation->address2)
                                            {{ $order->shippingContactInformation->address2 }}<br />
                                        @endif
                                        {{ $order->shippingContactInformation->city ?? '' }}
                                        {{ $order->shippingContactInformation->state ?? '' }}
                                        {{ $order->shippingContactInformation->zip ?? '' }}
                                        <br />
                                        {{ $order->shippingContactInformation->country->name ?? '' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td class="ship-to">
                        <table class="info-table">
                            <tbody>
                                <tr>
                                    <td class="text-right"><strong>{{ __('Order #:') }}</strong></td>
                                    <td class="info-text">{{ $order->number }}</td>
                                </tr>
                                <tr>
                                    <td class="text-right"><strong>{{ __('Date:') }}</strong></td>
                                    <td class="info-text">{{ user_date_time($order->ordered_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Items table --}}
        <table class="table items-table">
            <thead>
                <tr>
                    <th scope="col"><strong>{{ __('Item') }}</strong></th>
                    <th scope="col"><strong>{{ __('Description') }}</strong></th>
                    <th scope="col" class="text-center"><strong>{{ __('Qty') }}</strong></th>
                    @if ($showPricesOnSlip)
                        <th scope="col" class="text-right"><strong>{{ __('Price') }}</strong></th>
                        <th scope="col" class="text-right"><strong>{{ __('Total') }}</strong></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItemsForOrderSlip() ?? [] as $item)
                    <tr>
                        <td class="{{ $item->order_item_kit_id ? 'pl-1' : '' }}">{{ $item->sku }}</td>
                        <td class="{{ $item->order_item_kit_id ? 'pl-1' : '' }}">{{ $item->name }}</td>
                        <td class="text-center">{{ disable_kit_quantity_on_slips() && $item->product->type === \App\Models\Product::PRODUCT_TYPE_STATIC_KIT && !$item->parentOrderItem  ? '' : $item->quantityForOrderSlip() }}</td>
                        @if ($showPricesOnSlip)
                            <td class="text-right">{{ number_format($item->price, 2) }} {{ $currency }}</td>
                            <td class="text-right">{{ number_format($item->price * $item->quantityForOrderSlip(), 2) }} {{ $currency }}</td>
                        @endif
                    </tr>
                @endforeach
                <tr>
                    <td colspan="{{ 3 + ($showPricesOnSlip ? 2: 0) }}">
                        @if (!empty($order->gift_note))
                            <div class="mt-3 slip-textbox">
                                <strong>{{ __('Gift Note:') }}</strong> {!! $order->gift_note !!}
                            </div>
                        @endif
                        @if (!empty($order->slip_note))
                            <div class="mt-3 slip-textbox">
                                <strong>{{ __('Slip Note:') }}</strong> {!! $order->slip_note !!}
                            </div>
                        @endif

                        <div class="mt-3 slip-textbox">
                            {!! customer_settings($order->customer->id, \App\Models\CustomerSetting::CUSTOMER_SETTING_ORDER_SLIP_TEXT) !!}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
