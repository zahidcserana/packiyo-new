<?php

namespace App\Http\Controllers\Api\FrontendV1;

use App\Components\AutomationComponent;
use App\Exceptions\AutomationException;
use App\Http\Controllers\Controller;
use App\JsonApi\FrontendV1\Automations\AutomationRequest;
use App\JsonApi\FrontendV1\Customers\CustomerCollectionQuery;
use App\Models\Automation;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Throwable;

class AutomationController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    public function __construct(
        private readonly AutomationComponent $automationComponent)
    {
    }

    /**
     * @throws AutomationException
     */
    public function update(
        AutomationRequest   $request,
        Automation          $automation,
        AutomationComponent $automationComponent
    ): DataResponse
    {
        $validRequest = $request->validated();

        $newIsEnabled = (bool)$validRequest['is_enabled'];

        if ($newIsEnabled !== $automation->is_enabled)
        {
            $action = $newIsEnabled ? 'enable' : 'disable';
            $this->automationComponent->$action($automation);
        }

        $appliesTo = $validRequest['applies_to'];

        if ($appliesTo !== $automation->applies_to)
        {
            $automation->applies_to = $appliesTo;
            $automation->save();
        }

        $position = (int)$validRequest['position'];

        if ($position !== $automation->position)
        {
            $automation->move($position);
        }

        $name = $validRequest['name'];

        if ($name !== $automation->name)
        {
            $automation->name = $name;
            $automation->save();
        }

        return new DataResponse($automation);
    }

    /**
     * @throws Throwable
     */
    public function updatingAppliesToCustomers (
        Automation $automation,
        AutomationRequest $request,
        CustomerCollectionQuery $query,
        ):  Responsable
    {
        try {
            $customerIds = $this->getRelationshipIds($request);

            if ($customerIds) {
                $this->automationComponent->syncCustomersByIds($automation, $customerIds);
            }

            return DataResponse::make($automation)
                ->withQueryParameters($query);
        } catch (AutomationException $e) {
                return Error::make()->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->setDetail($e->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function attachingAppliesToCustomers (
        Automation $automation,
        AutomationRequest $request,
        CustomerCollectionQuery $query,
    ): Responsable
    {
        try {
            $customerIds = $this->getRelationshipIds($request);

            if ($customerIds) {
                $this->automationComponent->attachCustomersByIds($automation, $customerIds);
            }

            return DataResponse::make($automation)
                ->withQueryParameters($query);

        } catch (AutomationException $e) {
            return Error::make()->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->setDetail( $e->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function detachingAppliesToCustomers (
        Automation $automation,
        AutomationRequest $request,
        CustomerCollectionQuery $query,
    ): Responsable
    {
        try {
            $customerIds = $this->getRelationshipIds($request);

            if ($customerIds) {
                $this->automationComponent->detachCustomersByIds($automation, $customerIds);
            }

            return DataResponse::make($automation)
                ->withQueryParameters($query);

        } catch (AutomationException $e) {
            return Error::make()->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)->setDetail( $e->getMessage());
        }
    }
    private function getRelationshipIds(ResourceRequest $request) : array
    {
        return array_column($request->data, 'id');
    }
}
