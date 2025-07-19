<?php

use App\Models\{Customer, ShippingMethod, User};
use Behat\Behat\Tester\Exception\PendingException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\NewAccessToken;

/**
 * Used to reference values in larger chunks of text that are used as templates.
 */
trait HasPlaceholders
{
    protected array|null $placeholders = null;

    protected function getPlaceholders(): array
    {
        if (is_null($this->placeholders)) {
            $this->placeholders = [];
        }

        return $this->placeholders;
    }

    protected function addPlaceholder(string $placeholder, mixed $value): void
    {
        $this->placeholders = $this->getPlaceholders();
        $this->placeholders[$placeholder] = (string) $value;
    }

    protected function hasPlaceholder(string $placeholder): bool
    {
        return !is_null($this->placeholders) && array_key_exists($placeholder, $this->placeholders);
    }

    protected function renderWithPlaceholders(string $template): string
    {
        return render_small_template($template, $this->getPlaceholders());
    }
}
