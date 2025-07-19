@component('mail::message')
    The invoice for client {{$clientName}} for the period of {{$startDate}} to {{$endDate}} has been recalculated.
    Check invoice at: <a href="{{$link}}">link</a>
@endcomponent
