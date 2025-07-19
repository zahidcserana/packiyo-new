<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    'send_exported_file_after_seconds' => env('SEND_EXPORTED_FILE_AFTER_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        App\Providers\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Laravel\Sanctum\SanctumServiceProvider::class,

        /*
         * Package Service Providers...
         */
        Venturecraft\Revisionable\RevisionableServiceProvider::class,
        \Webpatser\Countries\CountriesServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        \App\Providers\UserComponentServiceProvider::class,
        \App\Providers\TaskTypeComponentServiceProvider::class,
        \App\Providers\TaskComponentServiceProvider::class,
        \App\Providers\SupplierComponentServiceProvider::class,
        \App\Providers\CustomerComponentServiceProvider::class,
        \App\Providers\WarehouseComponentServiceProvider::class,
        \App\Providers\ProductComponentServiceProvider::class,
        \App\Providers\OrderComponentServiceProvider::class,
        \App\Providers\OrderChannelComponentServiceProvider::class,
        \App\Providers\CycleCountBatchComponentServiceProvider::class,
        \App\Providers\HomeComponentServiceProvider::class,
        \App\Providers\ProfileComponentServiceProvider::class,
        \App\Providers\PrinterComponentServiceProvider::class,
        \App\Providers\PurchaseOrderComponentServiceProvider::class,
        \App\Providers\ReturnComponentServiceProvider::class,
        \App\Providers\ReturnStatusComponentServiceProvider::class,
        \App\Providers\WebhookComponentServiceProvider::class,
        \App\Providers\LinkComponentServiceProvider::class,
        \App\Providers\InventoryLogComponentServiceProvider::class,
        \App\Providers\SiteComponentServiceProvider::class,
        \App\Providers\LocationComponentServiceProvider::class,
        \App\Providers\TagComponentServiceProvider::class,
        \App\Providers\OrderStatusComponentServiceProvider::class,
        \App\Providers\ShippingBoxComponentServiceComponent::class,
        \App\Providers\PackingComponentServiceProvider::class,
        \App\Providers\PurchaseOrderStatusComponentServiceProvider::class,
        \App\Providers\ShipmentComponentServiceProvider::class,
        \App\Providers\RouteOptimizationComponentServiceProvider::class,
        \App\Providers\WebshipperShippingServiceProvider::class,
        \App\Providers\WebshipperCredentialComponentServiceProvider::class,
        \App\Providers\ToteComponentServiceProvider::class,
        \App\Providers\PickingCartComponentServiceProvider::class,
        \App\Providers\EditColumnComponentServiceProvider::class,
        \App\Providers\ShippingMethodMappingComponentServiceProvider::class,
        \App\Providers\CsvComponentServiceProvider::class,
        \App\Providers\ShippingCarrierComponentServiceProvider::class,
        \App\Providers\ShippingMethodComponentServiceProvider::class,
        \App\Providers\LocationTypeComponentServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,
        Igaster\LaravelCities\GeoServiceProvider::class,
        \App\Providers\EasypostShippingServiceProvider::class,
        \App\Providers\EasypostCredentialComponentServiceProvider::class,
        \App\Providers\ShippingComponentServiceProvider::class,
        \App\Providers\BulkShipComponentServiceProvider::class,
        \App\Providers\LotComponentServiceProvider::class,
        \App\Providers\PaymentComponentServiceProvider::class,
        \App\Providers\PickingBatchComponentServiceProvider::class,
        \App\Providers\BillingServiceProvider::class,
        OwenIt\Auditing\AuditingServiceProvider::class,
        \App\Providers\AutomationServiceProvider::class,
        \App\Providers\WholesaleServiceProvider::class,
        \App\Providers\TribirdShippingServiceProvider::class,
        \App\Providers\TribirdOrderChannelServiceProvider::class,
        \App\Providers\BulkPrintComponentServiceProvider::class,
        \App\Providers\AllocationComponentServiceProvider::class,
        \App\Providers\ExternalCarrierCredentialComponentServiceProvider::class,
        \App\Providers\ExternalCarrierShippingServiceProvider::class,
        \App\Providers\DataWarehouseComponentServiceProvider::class,
        \App\Providers\ZplConverterComponentServiceProvider::class,
        \App\Providers\AuditComponentServiceProvider::class,
        // TODO: Reactivate for calculating occupied inventory locations using MongoDB.
        Jenssegers\Mongodb\MongodbServiceProvider::class, // TODO: Should this be autoloaded?
        \App\Providers\FulfillmentBillingCalculatorServiceProvider::class,
        \App\Providers\ShipmentBillingCacheServiceProvider::class,
        \App\Providers\PickingBillingRateCacheProcessorProvider::class,
        \App\Providers\ShippingBillingRateCacheProcessorProvider::class,
        \App\Providers\BillingRequestValidatorProvider::class,
        \App\Providers\AddressBookComponentServiceProvider::class,
        // LaravelJsonApi\Laravel\ServiceProvider::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => \Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'PDF' => Barryvdh\DomPDF\Facade::class,
        'Countries' => \Webpatser\Countries\CountriesFacade::class
    ],
];
