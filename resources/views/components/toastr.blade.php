@props([
    'type' => 'success',
    'key' => null,
])

@if (Session::has($key ?? 'status'))
    @php
        // let's clean this up, should only be one way to flash the status messages, now there are 3.
        $messages = Session::get($key ?? 'status');

        if (!is_array($messages)) {
            $messages = [
                'type' => 'success',
                'message' => $messages
            ];
        }

        if (array_key_exists('type', $messages)) {
            $messages = [$messages];
        }
    @endphp

    <script>
        @foreach($messages as $message)
            @switch($type = \Illuminate\Support\Arr::get($message, 'type'))
                @case('info')
                @case('error')
                @case('warning')
                    toastr.{{ $type }}(@json(\Illuminate\Support\Arr::get($message, 'message')))
                    @break

                @default
                    toastr.success(@json(\Illuminate\Support\Arr::get($message, 'message')))
                    @break
            @endswitch
        @endforeach
    </script>
@endif

@if (! $errors->isEmpty())
    <script>
        @foreach ($errors->all() as $error)
            toastr.error(@json($error))
        @endforeach
    </script>
@endif
