<?php

use Behat\Gherkin\Node\PyStringNode;
use Illuminate\Support\MessageBag;

/**
 * Behat steps to test products.
 */
trait HasValidationErrors
{
    static array $errors = [];
    private MessageBag $messageBag;

    public function checkMessageBag(MessageBag $messageBag)
    {
        $this->messageBag = $messageBag;
        static::$errors = $messageBag->messages() ?? [];
    }

    public function hasErrorMessages(): bool
    {
        return $this->messageBag->count() > 0;
    }

    /**
     * @Then validation has :state
     */
    public function validationHas(string $state): void
    {
        switch ($state) {
            case 'failed':
                $this->assertTrue($this->hasErrorMessages());
                break;
            case 'passed':
                $this->assertFalse($this->hasErrorMessages());
                break;
        }
    }

    /**
     * @Then there is a following error for the field :fieldName
     */
    public function thereIsAFollowingErrorForTheField(string $fieldName, PyStringNode $message): void
    {
        $messages = collect(static::$errors)->filter(
            fn($item, $key) => $key == $fieldName && $message == $item[0]
        );

        $this->assertEquals(1, $messages->count());
    }
}
