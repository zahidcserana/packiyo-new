<?php

namespace App\Reports;

use App\Components\UserComponent;
use App\Features\InventorySnapshot;
use App\Http\Resources\ExportResources\InventorySnapshotReportExportResource;
use App\Http\Resources\InventorySnapshotReportTableResource;
use App\Http\Dto\Filters\Reports\InventorySnapshotReportFilterDto;
use App\Models\Customer;
use App\Models\OccupiedLocationLog;
use App\Models\Warehouse;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Laravel\Pennant\Feature;

class InventorySnapshotReport extends Report
{
    protected $reportId = 'inventory_snapshot';
    protected $dataTableResourceClass = InventorySnapshotReportTableResource::class;
    protected $exportResourceClass = InventorySnapshotReportExportResource::class;

    public function __construct(private readonly UserComponent $userComponent)
    {
        if (! Feature::for('instance')->active(InventorySnapshot::class)) {
            abort(404);
        }
    }

    protected function reportTitle()
    {
        return __('Inventory Snapshot');
    }

    public function view(Request $request): Factory|View|Application
    {
        $filterDto = $this->getFilterDto($request);

        return view('reports.view', [
            'reportId' => $this->reportId,
            'reportTitle' => $this->reportTitle(),
            'datatableOrder' => app('editColumn')->getDatatableOrder($this->reportId),
            'data' => $filterDto,
            'widgetsUrl' => $this->widget ? route('report.widgets', ['reportId' => $this->reportId]) : '',
            'searchPlaceholder' => __('Search by product name'),
        ]);
    }

    /**
     * @param  Request  $request
     * @return mixed|null
     */
    public function searchProductName(Request $request): mixed
    {
        return $request->get('search')['value'] === null || $request->get('search')['value'] === ''
            ? null
            : $request->get('search')['value'];
    }

    public function searchDate(?string $filteredDate): string|int
    {
        return $filteredDate ?? user_date_time(now()->subDay());
    }

    /**
     * @param  int  $filteredWarehouseId
     * @param  mixed  $customer
     * @return array|int[]
     */
    public function searchWarehouseIds(int $filteredWarehouseId, mixed $customer): array
    {
        return $filteredWarehouseId > 0
            ? [$filteredWarehouseId]
            : Warehouse::query()
                ->where('customer_id', $customer->is3plChild() ? $customer->parent->id : $customer->id)
                ->get('id')
                ->map(fn($warehouse) => $warehouse->id)
                ->toArray();
    }

    /**
     * @param  int  $filteredCustomerId
     * @param  array  $selectedCustomerIds
     * @return array|int[]
     */
    public function searchCustomerIds(int $filteredCustomerId, array $selectedCustomerIds): array
    {
        return $filteredCustomerId > 0 ? [$filteredCustomerId] : $selectedCustomerIds;
    }

    /**
     * @param  Request  $request
     * @return array
     */
    public function normalizedRequestFilter(Request $request, Customer $customer): array
    {
        $filter = $request->get('filter_form');
        $today = user_date_time(now());

        Validator::validate($filter, [
            'date' => ['required', 'date_format:Y-m-d', "before:{$today}"],
            'customer_id' => [$customer->is3plChild() ? 'prohibited' : 'nullable'],
        ]);

        if ($customer->is3plChild()) {
            // When it's a 3PL user, we need to force the customer_id to the customer's id
            $filter['customer_id'] = $customer->id;
        } else {
            $filter['customer_id'] = $filter['customer_id'] ?? 0;
        }

        $filteredCustomerId = (int) $filter['customer_id'];
        $filteredWarehouseId = (int) $filter['warehouse_id'];

        return [$filteredCustomerId, $filteredWarehouseId, $filter['date']];
    }

    /**
     * @param  mixed  $columnOrder
     * @param $name
     * @return array
     */
    public function sort(Request $request): array
    {
        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');

        $sortColumnName = 'product.name';
        $sortDirection = 'asc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        return [$sortColumnName, $sortDirection];
    }

    private function filters(Request $request): array
    {
        if ($this->userComponent->isClientCustomer()) {
            $customer = $this->userComponent->getSessionCustomer();
        } else {
            $customer = $this->userComponent->get3plCustomer();
        }
        $selectedCustomerIds = $this->userComponent->getSelectedCustomers()->pluck('id')->toArray();

        [$filteredCustomerId, $filteredWarehouseId, $filteredDate] = $this->normalizedRequestFilter($request, $customer);

        return [
            $this->searchCustomerIds($filteredCustomerId, $selectedCustomerIds),
            $this->searchWarehouseIds($filteredWarehouseId, $customer),
            $this->searchDate($filteredDate),
            $this->searchProductName($request)
        ];
    }

    protected function getQuery(Request $request): ?Builder
    {
        [$customerIds, $warehouseIds, $date, $searchProductName] = $this->filters($request);
        [$sortColumnName, $sortDirection] = $this->sort($request);

        return OccupiedLocationLog::query()
            ->when($searchProductName, fn (Builder $query) => $query->where('product.name', 'like', "%{$searchProductName}%"))
            ->whereIn('product.customer.id', $customerIds)
            ->whereIn('warehouse_id', $warehouseIds)
            ->where('calendar_date', $date)
            ->orderBy($sortColumnName, $sortDirection);
    }

    protected function getFilterDto(Request $request): ?Arrayable
    {
        $warehouseOptions = Warehouse::query()
            ->with('contactInformation')
            ->whereIn('customer_id', app('user')->getSelectedCustomers()->pluck('id'))
            ->get()
            ->mapWithKeys(fn (Warehouse $warehouse) => [
                $warehouse->id => $warehouse->contactInformation->name
            ]);

        return new InventorySnapshotReportFilterDto(
            $warehouseOptions,
        );
    }
}
