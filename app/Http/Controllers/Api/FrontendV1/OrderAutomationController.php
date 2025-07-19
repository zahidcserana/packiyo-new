<?php

namespace App\Http\Controllers\Api\FrontendV1;

use App\Components\AutomationComponent;
use App\Http\Controllers\Controller;
use App\JsonApi\FrontendV1\Automations\OrderAutomationRequest;
use App\Models\Automations\AppliesToCustomers;
use App\Models\Automations\OrderAutomation;
use App\Models\Customer;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousQuery;

/**
 * @todo TODO: This may not be needed at all, see https://laraveljsonapi.io/docs/1.0/routing/writing-actions.html#introduction
 */
class OrderAutomationController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update; // E.g. rename automation.
    // use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    // use Actions\UpdateRelationship;
    use Actions\AttachRelationship; // E.g. adding a customer the automation applies to.
    use Actions\DetachRelationship; // E.g. removing a customer the automation applies to.

    protected AutomationComponent $automationComponent;

    public function __construct(AutomationComponent $automationComponent)
    {
        $this->automationComponent = $automationComponent;
    }

    protected function creating(OrderAutomationRequest $request, AnonymousQuery $query): Responsable
    {
        $data = $request->validationData();
        $targetEvents = array_map(fn (string $eventSlug) => $this->automationComponent->events->get($eventSlug)->type, $data['target_events']);
        $ownerCustomer = Customer::find($data['customer']['id']);

        $automation = new OrderAutomation([
            'target_events' => $targetEvents,
            'applies_to' => $ownerCustomer->isStandalone() ? AppliesToCustomers::OWNER : $data['applies-to'],
            'name' => $data['name']
        ]);

        $automation->position = OrderAutomation::where('customer_id', $ownerCustomer->id)->count() + 1;
        $automation->customer()->associate($ownerCustomer);
        $automation->save();
        $automation->originalRevision()->associate($automation);
        $automation->save();
        $automation->revisions()->attach($automation->id);

        if ($automation) {
            return DataResponse::make($automation)
                ->withQueryParameters($query)
                ->didCreate();
        }

        return Error::make()->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->setDetail('Could not create automation.');
    }
}
