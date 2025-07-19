<?php

use Laravel\Dusk\Browser;
use Illuminate\Support\Collection;

/**
 * Behat steps to navigate a web app using a web driver browser.
 */
trait BrowserSteps
{
    /**
     * @Given I navigate to :url
     */
    public function iNavigateTo(string $url)
    {
        $this->browse(function (Browser $browser) use ($url) {
            $browser->visit($url)
                ->screenshot('test_screenshot');
        });
    }

    /**
     * @Then I should see :text
     */
    public function iShouldSee(string $text): void
    {
        Collection::make(static::$browsers)->each->assertSee($text);
    }

    /**
     * @Given I type :text into :field
     */
    public function iTypeInto(string $text, string $field): void
    {
        Collection::make(static::$browsers)->each->type($field, $text);
    }

    /**
     * @When I press :button
     */
    public function iPress(string $button)
    {
        Collection::make(static::$browsers)->each->press($button);
    }

    /**
     * @Then the path should be :path
     */
    public function thePathShouldBe(string $path): void
    {
        Collection::make(static::$browsers)->each->assertPathIs($path);
    }
}
