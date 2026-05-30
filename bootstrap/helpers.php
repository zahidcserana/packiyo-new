<?php

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use App\Models\UserSetting;
use App\Models\CustomerSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;
use OwenIt\Auditing\Auditable;

if (!function_exists('dot')) {
    function dot($key) {
        $key = str_replace(['[', ']'], ['.', ''], $key);

        return $key;
    }
}

if (!function_exists('route_method')) {
    function route_method()
    {
        if (empty(Route::current())) {
            return '';
        }

        return Route::current()->methods()[0] ?? '';
    }
}

if (!function_exists('user_settings')) {
    function user_settings($key = null, $default = null, ?int $user_id = null)
    {
        $userId = $user_id ?? auth()->user()->id ?? null;

        if (!$userId) {
            return null;
        }
        if ($key == null) {
            $settings = [];


            foreach (UserSetting::USER_SETTING_KEYS as $key) {
                $settings[$key] = user_settings($key, user_id: $userId);
            }


            return $settings;
        } else {
            $cacheKey = 'user_setting_' . $userId . '_' . $key;
            $setting = Cache::get($cacheKey);

            if (is_null($setting)) {
                $userSetting = UserSetting::firstOrCreate([
                    'user_id' => $userId,
                    'key' => $key
                ]);

                if (!$userSetting->value && $default) {
                    $userSetting->update([
                        'value' => $default
                    ]);
                }

                $setting = $userSetting->value;

                Cache::put($cacheKey, $setting);
            }

            return $setting;
        }
    }
}

if (!function_exists('customer_settings')) {
    function customer_settings($customerId, $key = null, $default = null)
    {
        if (!$customerId) {
            return $default;
        }

        if (is_null($key)) {
            $settings = [];

            foreach (CustomerSetting::CUSTOMER_SETTING_KEYS as $settingKey) {
                $settings[$settingKey] = customer_settings($customerId, $settingKey);
            }

            return $settings;
        } else {
            $cacheKey = 'customer_setting_' . $customerId . '_' . $key;
            $setting = Cache::get($cacheKey);

            if (is_null($setting)) {
                $customerSetting = CustomerSetting::firstOrCreate([
                    'customer_id' => $customerId,
                    'key' => $key
                ]);

                if (!$customerSetting->value && $default) {
                    $customerSetting->update([
                        'value' => $default
                    ]);
                }

                $setting = $customerSetting->value;

                Cache::put($cacheKey, $setting);
            }

            return $setting;
        }
    }
}

if (!function_exists('user_timezone')) {
    function user_timezone(): string
    {
        return user_settings(UserSetting::USER_SETTING_TIMEZONE) ?? env('DEFAULT_TIME_ZONE') ?? 'UTC';
    }
}

if (!function_exists('user_date_time')) {
    function user_date_time($date, $dateHours = false): string
    {
        $dateTimeFormat = user_settings(UserSetting::USER_SETTING_DATE_FORMAT, env('DEFAULT_DATE_FORMAT'));
        $timeZone = user_timezone();

        if (!$dateTimeFormat) {
            $dateTimeFormat = 'Y-m-d';
        }

        if ($dateHours) {
            $dateTimeFormat .= ' ' . UserSetting::USER_SETTING_DEFAULT_TIME_FORMAT;
        }

        return Carbon::parse($date)->timezone($timeZone)->format($dateTimeFormat);
    }
}

if (!function_exists('user_start_end_date_diff')) {
    function user_start_end_date_diff(Carbon $startDate, Carbon $endDate, bool $inSeconds = false): string
    {
        $timeZone = user_timezone();

        $startDate->setTimezone($timeZone);
        $endDate->setTimezone($timeZone);

        if ($inSeconds) {
            return $startDate->diffInSeconds($endDate);
        }

        $dateDiff = $endDate->diff($startDate);

        if ($dateDiff->d > 0) {
            $formattedDiff = $endDate->diffForHumans($startDate, ['short' => true]);
        } elseif ($dateDiff->h > 0) {
            $formattedDiff = $dateDiff->format('%hh %im %ss');
        } else {
            $formattedDiff = $dateDiff->format('%im %ss');
        }

        return $formattedDiff;
    }
}

if (!function_exists('localized_date')) {
    function localized_date(Carbon|string $date): string
    {
        $dateFormat = user_settings(UserSetting::USER_SETTING_DATE_FORMAT, env('DEFAULT_DATE_FORMAT'));

        return Carbon::parse($date)->startOfDay()->format($dateFormat);
    }
}

if (!function_exists('weight_unit_conversion')) {
    function weight_unit_conversion($value, $requestedUnit = '')
    {
        $value = (float) $value;

        // TODO: Why is this constant treated as a variable? Should this not reference the customer level setting?
        if (Customer::WEIGHT_UNIT_DEFAULT === 'oz' && $requestedUnit === 'lb') {
            return $value / 16;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'kg' && $requestedUnit === 'lb') {
            return $value / 0.45359237;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'g' && $requestedUnit === 'lb') {
            return $value * 0.00220462;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'oz' && $requestedUnit === 'g') {
            return $value * 453.59237;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'lb' && $requestedUnit === 'g') {
            return $value / 0.0022046;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'kg' && $requestedUnit === 'g') {
            return $value * 1000;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'lb' && $requestedUnit === 'oz') {
            return $value * 16;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'kg' && $requestedUnit === 'oz') {
            return $value / 0.02834952;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'g' && $requestedUnit === 'oz') {
            return $value / 0.03527396195;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'lb' && $requestedUnit === 'kg') {
            return $value * 0.45359237;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'oz' && $requestedUnit === 'kg') {
            return $value * 0.02834952;
        }

        if (Customer::WEIGHT_UNIT_DEFAULT === 'g' && $requestedUnit === 'kg') {
            return $value / 1000;
        }

        return $value;
    }
}

if (!function_exists('threepl_logo')) {
    function threepl_logo(): string
    {
        $default = asset('img/packiyo-logo-on-transparent.png');
        $customer = app('user')->getSessionCustomer();

        if (!$customer) {
           return $default;
        }

        return $customer->threeplLogo ?
                $customer->threeplLogo->source :
                ($customer->parent && $customer->parent->threeplLogo ? $customer->parent->threeplLogo->source : $default);
    }
}

if (!function_exists('get_app_link')) {
    function get_app_link($customer = null): string
    {
        if (!$customer) {
            $customer = app('user')->getSessionCustomer();
            if (!$customer) return '';
        }
        
        if ($customer->store_domain) {
            return 'https://' . $customer->store_domain;
        }

        return $customer->slug ?
                'https://' . $customer->slug . '.' . env('APP_DOMAIN') :
                ($customer->parent && $customer->parent->slug ? 'https://' . $customer->parent->slug . '.' . env('APP_DOMAIN') : '');
    }
}

if (!function_exists('login_logo')) {
    function login_logo(): string
    {
        return Feature::for('instance')->value(\App\Features\LoginLogo::class) ?? asset('img/packiyo-logo-on-transparent.png');
    }
}

if (!function_exists('disable_kit_quantity_on_slips')) {
    function disable_kit_quantity_on_slips(): bool
    {
        return Feature::for('instance')->active(\App\Features\DisableQuantityForKitInSlips::class);
    }
}

if (!function_exists('menu_item_visible')) {
    function menu_item_visible($menuItem): bool
    {
        $customer = app('user')->getSessionCustomer();

        if ($customer && $customer->parent && in_array($menuItem, config('settings.client_excluded_menu_items'))) {
            return false;
        }

        return true;
    }
}

if (!function_exists('paper_height')) {
    function paper_height($customerId, $type = ''): float|int
    {
        $defaultHeight = [
            'label' => 192,
            'document' => 297,
            'barcode' => 30,
            'footer' => 0,
        ];

        $height = customer_paper_height($customerId);

        if (empty($height[$type]) && $customerId = Customer::find($customerId)->parent_id) {
            $height = customer_paper_height($customerId);
        }

        if (empty($height[$type])) {
            return $defaultHeight[$type] * 2.83464567;
        }

        return paper_size_in_pt($customerId, $height[$type]);
    }
}

if (!function_exists('paper_width')) {
    function paper_width($customerId, $type = ''): float|int
    {
        $defaultWidth = [
            'label' => 102,
            'document' => 210,
            'barcode' => 100,
        ];

        $width = customer_paper_width($customerId);

        if (empty($width[$type]) && $customerId = Customer::find($customerId)->parent_id) {
            $width = customer_paper_width($customerId);
        }

        if (empty($width[$type])) {
            return $defaultWidth[$type] * 2.83464567;
        }

        return paper_size_in_pt($customerId, $width[$type]);
    }
}

if (!function_exists('paper_size_in_pt')) {
    function paper_size_in_pt($customerId, $value, $dimension = null): float|int
    {
        $dimension = is_null($dimension) ? customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_DIMENSIONS_UNIT) : $dimension;

        if ($dimension === 'in') {
            return $value * 72;
        }

        if ($dimension === 'cm') {
            return $value * 28.3465;
        }

        return $value * 2.83464567;
    }
}

if (!function_exists('customer_paper_height')) {
    function customer_paper_height($customerId)
    {
        return [
            'label' => customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_LABEL_SIZE_HEIGHT),
            'document' => customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_DOCUMENT_SIZE_HEIGHT),
            'barcode' => customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_BARCODE_SIZE_HEIGHT),
            'footer' => customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_DOCUMENT_FOOTER_HEIGHT),
        ];
    }
}

if (!function_exists('customer_paper_width')) {
    function customer_paper_width($customerId)
    {
        return [
            'label' => customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_LABEL_SIZE_WIDTH),
            'document' => customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_DOCUMENT_SIZE_WIDTH),
            'barcode' => customer_settings($customerId, CustomerSetting::CUSTOMER_SETTING_BARCODE_SIZE_WIDTH),
        ];
    }
}

if (!function_exists('is_auditable')) {
    function is_auditable(Model $model)
    {
        return in_array(Auditable::class, class_uses_recursive(get_class($model)));
    }
}

if (!function_exists('transpose_matrix')) {
    /**
     * @author ChatGPT
     */
    function transpose_matrix(array $matrix): array
    {
        $rows = count($matrix);
        $columns = max(array_map('count', $matrix));
        $transposed = [];

        for ($i = 0; $i < $columns; $i++) {
            $transposedRow = [];

            for ($j = 0; $j < $rows; $j++) {
                $transposedRow[] = isset($matrix[$j][$i]) ? $matrix[$j][$i] : null;
            }

            $transposed[] = $transposedRow;
        }

        return $transposed;
    }
}

if (!function_exists('add_business_days')) {
    /**
     * @author ChatGPT
     */
    function add_business_days(Carbon $date, int $days): Carbon
    {
        $newDate = $date->copy();
        $direction = $days >= 0 ? 1 : -1;

        while ($days != 0) {
            $newDate->addWeekday($direction);

            if (!$newDate->isWeekend()) {
                $days -= $direction;
            }
        }

        return $newDate;
    }
}

if (!function_exists('sub_business_days')) {
    function sub_business_days(Carbon $date, int $days): Carbon
    {
        return add_business_days($date, -$days);
    }
}

if (!function_exists('get_nested_value')) {
    /**
     * @author ChatGPT
     */
    function get_nested_value(Model $model, string $path): string|null
    {
        return collect(explode('.', $path))->reduce(
            fn ($carry, $property) => is_object($carry) && isset($carry->$property) ? $carry->$property : null,
            $model
        );
    }
}

if (!function_exists('render_attributes_template')) {
    /**
     * @author ChatGPT
     */
    function render_attributes_template(Model $operation, string $template, ?array $placeholders = null): string
    {
        preg_match_all('/{{(.*?)}}/', $template, $matches);
        $placeholders = array_unique($matches[1]);

        if (is_array($placeholders)) {
            $placeholders = array_intersect($placeholders);
        }

        $values = collect($placeholders)->mapWithKeys(
            fn (string $attribute) => [$attribute => get_nested_value($operation, $attribute)]
        );

        foreach ($values->toArray() as $placeholder => $value) {
            $template = str_replace("{{{$placeholder}}}", $value, $template);
        }

        return $template;
    }
}

if (!function_exists('allow_void_label')) {
    function allow_void_label(): bool
    {
        $customer = app('user')->getSessionCustomer();

        if ($customer && $customer->parent && !customer_settings($customer->id, CustomerSetting::CUSTOMER_SETTING_ALLOW_CLIENT_VOID_LABEL)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('render_small_template')) {
    function render_small_template(string $template, ?array $args = null): string
    {
        return empty($args)
            ? $template
            : str_replace(array_map(fn ($key) => ':' . $key . ':', array_keys($args)), array_values($args), $template);
    }
}

if (!function_exists('parse_int_or_float')) {
    function parse_int_or_float(string $value): int|float
    {
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? floatval($value) : intval($value);
        } else {
            throw new ValueError('The given value is not numeric: "' . $value . '".');
        }
    }
}

if (!function_exists('is_divisible')) {
    function is_divisible(mixed $numerator, mixed $denominator): bool
    {
        if ($denominator == 0) {
            return false;  // Avoid division by zero.
        }

        return $numerator % $denominator == 0;
    }
}

if (!function_exists('request_time')) {
    function request_time(): int
    {
        return intval(microtime(true) - LARAVEL_START);
    }
}

if (!function_exists('date_format_js')) {
    function date_format_js(): mixed
    {
        if (empty(auth()->user())) {
            return '';
        }

        $jsDateformats = config('settings.js_dateformats');

        $userDateFormat = user_settings(UserSetting::USER_SETTING_DATE_FORMAT, env('DEFAULT_DATE_FORMAT'));

        return Arr::get($jsDateformats, $userDateFormat, reset($jsDateformats));
    }
}

if (!function_exists('get_distributed_queue_name')) {
    function get_distributed_queue_name(string $queue): string
    {
        $queueSize = config('queue.sizes.' . $queue, 1);

        if ($queueSize > 1) {
            $queue = $queue . '-' . rand(1, $queueSize);
        }

        return $queue;
    }
}
