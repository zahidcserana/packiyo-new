<?php

return [
    'max_tries_per_request' => (int) env('PRINTING_SERVICE_MAX_TRIES_PER_REQUEST', 10),
    'sleep_duration_after_try' => (int) env('PRINTING_SERVICE_SLEEP_DURATION_AFTER_TRY', 1),
];
