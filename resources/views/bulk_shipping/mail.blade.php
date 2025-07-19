@component('mail::message')
# Batch Shipped

Your batch has been shipped!

@component('mail::table')
| **Batch ID**   | **{{ $bulkShipBatch->id }}**                          |
| :------------- | :---------------------------------------------------- |
| Orders shipped | {{ $bulkShipBatch->orders->count() }}                 |
| Created at     | {{ user_date_time($bulkShipBatch->created_at, true) }} |
| Shipped at     | {{ user_date_time($bulkShipBatch->shipped_at, true) }} |
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
