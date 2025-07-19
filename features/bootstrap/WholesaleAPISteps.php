<?php

use App\Models\EDI\Providers\CrstlASN;
use App\Models\EDI\Providers\CrstlPackingLabel;
use App\Models\Printer;
use Behat\Behat\Tester\Exception\PendingException;
use Illuminate\Support\Facades\Http;

/**
 * Behat steps to test the internal wholesale API.
 */
trait WholesaleAPISteps
{
    private ?\Mockery\MockInterface $logSpy = null;

    /**
     * @Then it should log the error after these tries
     */
    public function weShouldveLoggedTheError(): void
    {
        if ($this->logSpy) {
            $this->logSpy->shouldHaveReceived('warning')
                ->withArgs(fn ($message) => str_contains($message, 'Could not download file from URL:') && str_contains($message, 'Status code: 404'))
                ->once();
            $this->logSpy = null;
        } else {
            throw new PendingException(text: 'No log spy was set.');
        }
    }

    /**
     * @Then it should try to download the file :amount times
     */
    public function itShouldveTriedTimes(int $amount): void
    {
        Http::assertSentCount($amount);
    }

    /**
     * @When the labels' content aren't available for download
     */
    public function theLabelsContentArentAvailable(): void
    {
        Http::fake([
            // Stub a JSON response for GitHub endpoints...
            'storage.googleapis.com/*' => Http::response([], 404),
        ]);
    }

    /**
     * @When the web app checks if the GS1-128 labels are available until they are
     */
    public function theWebAppChecksIfTheGsLabelsAreAvailableUntilTheyAre(): void
    {
        $this->logSpy = \Illuminate\Support\Facades\Log::spy();

        $url = route('packing.getEDILabels', ['shipment' => $this->shipment->id]);
        $loops = 20;

        foreach (range(1, $loops) as $loop) { // Yeah, I know about the regular for syntax.
            $callback = fn () => $this->getJson($url, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);

            $response = static::record($callback);
            $responseData = $response->getData();

            if ($responseData->asn->packing_labels) {
                break;
            }
        }

        if ($loop === $loops) {
            throw new PendingException(text: 'TODO: After 20 loops the labels were still unavailable.');
        }

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @Given the first EDI label has no content
     */
    public function theFirstEdiLabelHasNoContent(): void
    {
        $asn = CrstlASN::query()
            ->where('shipment_id', $this->shipment->id)
            ->firstOrFail();
        $label = $asn->packingLabels->first();
        $label->content = null;
        $label->save();
    }

    /**
     * @When the web app requests to see the first GS1-128 label
     */
    public function theWebAppRequestsToSeeTheGsLabels(): void
    {
        $asn = CrstlASN::query()
            ->where('shipment_id', $this->shipment->id)
            ->firstOrFail();
        $url = route('shipment.packing-label', ['shipment' => $this->shipment->id, 'asn' => $asn->id, 'packingLabel' => $asn->packingLabels->first()->id]);
        $response = $this->get($url);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }

    /**
     * @Then the web app should show the GS1-128 label
     */
    public function theWebAppShouldShowTheGsLabels(): void
    {
        $response = $this->getResponseInScope();
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * @Then the web app should show the GS1-128 label information as json
     */
    public function theWebAppShouldShowTheGsLabelsInformationHasJson(): void
    {
        $firstEdiLabel = CrstlASN::query()
            ->where('shipment_id', $this->shipment->id)
            ->firstOrFail()
            ->packingLabels
            ->first();

        $response = $this->getResponseInScope();
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJson([
            'url' => $firstEdiLabel->signed_url,
            'expires_at' => $firstEdiLabel->signed_url_expires_at->toIso8601String()
        ]);
    }

    /**
     * @When the web app prints the GS1-128 labels
     */
    public function theWebAppPrintsTheGsLabels(): void
    {
        $printer = Printer::factory()->create(); // TODO: Maybe receive as arg?

        // Fake unexpired label URLs.
        $asn = CrstlASN::where('shipment_id', $this->shipment->id)->firstOrFail();
        $asn->packingLabels->each(function (CrstlPackingLabel $label) {
            $label->signed_url_expires_at = now()->addHour();
            $label->save();
        });

        $url = route('packing.printEDILabels', ['shipment' => $this->shipment->id]);
        $response = $this->postJson($url, [
            'printer_id' => $printer->id
        ], [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        if (method_exists($this, 'setResponseInScope')) {
            $this->setResponseInScope($response);
        }
    }
}
