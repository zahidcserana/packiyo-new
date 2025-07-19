<?php

namespace App\Console\Commands;

use App\Components\WholesaleIntegrationsComponent;
use App\Exceptions\WholesaleException;
use App\Models\Customer;
use App\Models\EDI\Providers\CrstlEDIProvider;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Response;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use stdClass;

class ConfigureEDI extends Command
{
    const DEFAULT_PROD_CONNECTION_NAME = 'Crstl EDI';
    const DEFAULT_SANDBOX_CONNECTION_NAME = 'Crstl EDI Sandbox';
    const PROD_ENV_CONNECTION = 'production';
    const SANDBOX_ENV_CONNECTION = 'sandbox';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'edi:configure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure an EDI provider integration for a client.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(protected WholesaleIntegrationsComponent $integrations)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 3PL client or standalone customer.
        $chosenOwnerCustomer = $this->getChosenOwnerCustomer(self::getOwnerCustomerChoices());
        $chosenEnv = $this->getConnectionEnv();
        $isSandbox = $chosenEnv == static::SANDBOX_ENV_CONNECTION;
        $loginData = $this->getAPITokens($this->getUserEmail(), $this->getUserPassword(), $isSandbox);
        $chosenName = $this->getConnectionName($isSandbox);

        $provider = CrstlEDIProvider::fromLogin($chosenOwnerCustomer, $chosenName, $loginData);
        $provider->save();

        $this->info('Your Crstl EDI ' . $chosenEnv . ' connection was created successfully.');

        return 0;
    }

    protected function getChosenOwnerCustomer(array $customerChoices): Customer
    {
        $customerName = $this->anticipate(
            __('Which customer should own the integration?'),
            $customerChoices
        );

        return Customer::whereHas('contactInformation', function (Builder $query) use (&$customerName) {
            $query->where('name', $customerName);
        })->firstOrFail();
    }

    protected static function getOwnerCustomerChoices(): array
    {
        return Customer::with(['contactInformation'])
            ->where('allow_child_customers', false)
            ->has('contactInformation')
            ->get()
            ->mapWithKeys(fn (Customer $customer) => [$customer->id => $customer->contactInformation->name])
            ->toArray();
    }

    protected function getAPITokens(string $email, string $password, bool $isSandbox = false): stdClass
    {
        $this->info('Getting your API tokens...');

        try {
            $response = $this->integrations->buildRequest()
                ->login($email, $password, sandbox: $isSandbox)
                ->send();
        } catch (ClientException $exception) {
            if (in_array($exception->getResponse()->getStatusCode(), [Response::HTTP_BAD_REQUEST, Response::HTTP_UNAUTHORIZED])) {
                $message = $this->integrations->decodeBody($exception->getResponse()->getBody())->data->message;

                throw new WholesaleException('Could not login: ' . $message);
            }
        }

        return $this->integrations->decodeBody($response->getBody());
    }

    protected function getUserEmail(): string
    {
        do {
            $email = trim($this->ask(__('What is the email of the user for which we\'ll create an API?')));
        } while (empty($email));

        return $email;
    }

    protected function getUserPassword(): string
    {
        do {
            $password = trim($this->secret(__('And what is their password?')));
        } while (empty($password));

        return $password;
    }

    protected function getConnectionEnv(): string
    {
        $env = $this->choice(
            'Which Crstl environment is this connection for?',
            [static::PROD_ENV_CONNECTION, static::SANDBOX_ENV_CONNECTION],
            0 // Default to the first option: production.
        );

        return $env;
    }

    protected function getConnectionName(bool $isSandbox = false): string
    {
        do {
            $name = trim($this->ask(
                __('What should this connection be named?'),
                $isSandbox ? static::DEFAULT_SANDBOX_CONNECTION_NAME : static::DEFAULT_PROD_CONNECTION_NAME
            ));
        } while (empty($name));

        return $name;
    }
}
