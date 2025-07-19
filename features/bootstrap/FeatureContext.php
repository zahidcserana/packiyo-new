<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Tests\TestCase;
use Behat\Behat\Tester\Exception\PendingException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Illuminate\Contracts\Console\Kernel;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends TestCase implements Context
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    protected $response;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(string $env)
    {
        if (strpos(strtolower($env), strtolower('prod')) !== false) {
            throw new ValueError("You should probably not do that.");
        }

        putenv('APP_ENV=' . $env);
        parent::setUp();
    }

    /** @BeforeScenario */
    // public function before(BeforeScenarioScope $scope)
    // {
    //     // $this->artisan('migrate');

    //     // $this->app[Kernel::class]->setArtisan(null);

    //     // $this->seed();
    // }

    /** @AfterScenario */
    // public function after(AfterScenarioScope $scope)
    // {
    //     $this->artisan('migrate:rollback');
    // }

    /**
     * @When I visit the path :path
     */
    public function iVisitThePath($path)
    {
        $response = $this->get('/');
        $this->response = $response;
    }

    /**
     * @Then I should be redirected to :path
     */
    public function iShouldBeRedirectedTo($path)
    {
        $this->response->assertRedirectContains($path);
    }

    /**
     * @Then I should see the text :text
     */
    public function iShouldSeeTheText($text)
    {
        $this->assertContains($text, $this->response->getContent());
    }

    /**
     * @Given I log in as the user :user with the password :pass
     */
    public function iLogInAsTheUserWithThePassword(string $user, string $pass): void
    {
        // Run the DatabaseSeeder...
        $this->seed();
        $this->assertDatabaseCount('users', 1);
        $this->response = $this->withHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'en-US,en;q=0.9,fr;q=0.8,es;q=0.7',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cookie' => 'sidenav-state=unpinned; XSRF-TOKEN=eyJpdiI6IjNOOFpKZVo0RCswU3d4azg4Qk1Ic2c9PSIsInZhbHVlIjoiSTBOK2g2blBTeWdTaWdJWEUvYTNHSkRrL0RZYk9ERTRMS0hJbkhWYmxRTkVkZXR2TUZmSDNudlI5NUUxd3ovV2I5ZDNrY3h0UzNNUnVSODFTNkF3UlZwTjNTNzJwSTJoeXZwYStlU01tZnNwcVRzUE1ZY3ZGZDZYUFpNSktQaWsiLCJtYWMiOiJlNGM1OGNjZWI2MTYzYjcwYzY0Y2MwOTc3NDk1M2I0NzQxOTA0Nzk2YThmMzU1NTcyODMwOGVkMzNlOWFlYmY2IiwidGFnIjoiIn0%3D; packiyo_session=eyJpdiI6ImRZZGhVREdKWmsrTjAzWi90OTN5Znc9PSIsInZhbHVlIjoiWWJCNCswZzNjaFRyUWh4QWpHYWFHa3dUYmtNc2V6QTJxZlNuVmExK3ZMaENtaGJNK0w5WkIvVzkzMHhRNHozOUh6ZE93RHZvT2R6M3RPbEtOV3l1dm1CMnJCbFdCY21VYWZ6dnFaL2FkMEdqaEZTcjlIOVJtYThYa2ttN0pkNXkiLCJtYWMiOiI4YzdkYTZkMjhmMzJiODEyNTc2NjlkZDYxYjM3NWRmNzkzOTg4ZWNlOGZiZDI4ZGVmZjQ4YjA5NjZkY2Y0MTRmIiwidGFnIjoiIn0%3D',
            'Origin' => 'http://localhost',
            'Referer' => 'http://localhost/login',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Google Chrome";v="113", "Chromium";v="113", "Not-A.Brand";v="24"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
        ])
        ->post('/login', [
            '_token' => 'PRVIsdgCLb7OR8E3zd7389sgaRKZ0Ty3KSby4mFi',
            'timezone' => 'America/Buenos_Aires',
            'email' => $user,
            'password' => $pass,
        ]);

        $this->response->assertRedirectContains('/dashboard');
    }
}
