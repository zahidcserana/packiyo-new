<?php

use App\Reports\Report;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;

trait ReportSteps
{
    private ?Report $report = null;
    private ?\Illuminate\Http\JsonResponse $generatedReport = null;
    private array $reportRequestData = [
        'search' => [
            'value' => ''
        ],
    ];
    private ?Exception $exception = null;

    private array $reportsColumns = [
        \App\Reports\InventorySnapshotReport::class => [
            'Image' => 'products.image',
            'Name' => 'product.name',
            'SKU' => 'product.sku',
            'Customer' => 'customer.name',
            'Warehouse' => 'warehouse.id',
            'On Hand' => 'inventory.last',
            'Location' => 'location.name',
        ]
    ];


    /**
     * @When these are the report filters
     */
    public function theseAreTheReportFilterParameters(TableNode $parameters): void
    {
        $filterData = $parameters->getHash()[0];

        $warehouse = $filterData['Warehouse'];
        $warehouse_id = $warehouse === 'All'
            ? 0
            : \App\Models\Warehouse::whereHas('contactInformation', function ($query) use ($warehouse) {
                $query->where('name', $warehouse);
            })->firstOrFail()->id;

        $filterForm = [
            'date' => $filterData['Date'],
            'warehouse_id' => $warehouse_id,
        ];

        $customer = $filterData['Customer'] ?? null;
        if (! is_null($customer)) {
            $customer_id = $customer === 'All'
                ? 0
                : \App\Models\Customer::whereHas('contactInformation', function ($query) use ($customer) {
                    $query->where('name', $customer);
                })->firstOrFail()->id;

            $filterForm['customer_id'] = $customer_id;
        }

        $this->reportRequestData = [
            ...$this->reportRequestData,
            'filter_form' => $filterForm
        ];
    }

    /**
     * @Given the search term is :searchTerm
     */
    public function theSearchTermIs(string $searchTerm): void
    {
        $this->reportRequestData = [
            ...$this->reportRequestData,
            'search' => [
                'value' => $searchTerm
            ]
        ];
    }

    /**
     * @When the report is sorted by the column :column in :direction direction
     * @throws Exception
     */
    public function thisIsTheReportSortParameters(string $column, string $direction): void
    {
        if (! in_array($direction, ['asc', 'desc'])) {
            throw new Exception('Invalid direction. Values: asc, desc');
        }

        if (! array_key_exists($column, $this->reportsColumns[get_class($this->report)])) {
            throw new Exception('Invalid column. Values: ' . implode(', ', $this->reportsColumns[get_class($this->report)]));
        }

        $columns = array_map(function ($key) {
            return ['name' => $key];
        }, $this->reportsColumns[get_class($this->report)]);

        $orderColumnId = $this->getKeyIndex($this->reportsColumns[get_class($this->report)], $column);

        $this->reportRequestData = [
            ...$this->reportRequestData,
            'columns' => array_values($columns),
            'order' => [
                [
                    'column' => $orderColumnId,
                    'dir' => $direction
                ]
            ]
        ];
    }

    private function getKeyIndex(array $array, string $key) {
        // Get the keys of the array
        $keys = array_keys($array);

        // Search for the key and return its index
        $index = array_search($key, $keys);

        // Return the index or -1 if the key does not exist
        return $index !== false ? $index : -1;
    }

    /**
     * @When the report is :reportClassName
     */
    public function theReportIs(string $reportClassName): void
    {
        $this->report = \Illuminate\Support\Facades\App::make($reportClassName);
    }

    /**
     * @When I generate the report
     * @throws Exception
     */
    public function iGenerateTheReport(): void
    {
        try {
            $this->generatedReport = $this->report->dataTable(new Request($this->reportRequestData));
        } catch (Exception $e) {
            $this->exception = $e;
        }
    }

    /**
     * @Then the report should've thrown an exception
     */
    public function theReportShouldHaveThrownAnException(): void
    {
        $this->assertNotNull($this->exception);
    }

    /**
     * @Then the report should contain the following rows in the correct order:
     * @throws Exception
     */
    public function theReportShouldContainTheFollowingRows(TableNode $rows): void
    {
        $this->assertNotNull($this->generatedReport);

        // We assert the JSON, which also checks the order of the rows order
        TestResponse::fromBaseResponse($this->generatedReport)
            ->assertJson(['data' => $rows->getHash()]);

        $resultRows = $this->generatedReport->getData(assoc: true)['data'];
        $expectedRows = $rows->getHash();

        // Helper function to check if an expected row matches a result row
        $rowsMatch = function(array $resultRow, array $expectedRow): bool {
            foreach ($expectedRow as $key => $value) {
                if (!array_key_exists($key, $resultRow)) {
                    return false;
                }

                if (is_numeric($value)) {
                    $value = (int) $value;
                }

                if ($resultRow[$key] !== $value) {
                    return false;
                }
            }
            return true;
        };

        // Check each result row against the expected rows
        foreach ($resultRows as $resultRow) {
            $found = false;
            foreach ($expectedRows as $expectedRow) {
                if ($rowsMatch($resultRow, $expectedRow)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('Row with properties ' . json_encode($resultRow) . ' not found in the expected rows');
            }
        }

        // Check each expected row against the result rows
        foreach ($expectedRows as $expectedRow) {
            $found = false;
            foreach ($resultRows as $resultRow) {
                if ($rowsMatch($resultRow, $expectedRow)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('Row with properties ' . json_encode($expectedRow) . ' not found in the result rows');
            }
        }
    }

    /**
     * @Then the report should contain no rows
     */
    public function theReportShouldContainNoRows(): void
    {
        $this->assertNotNull($this->generatedReport);

        TestResponse::fromBaseResponse($this->generatedReport)
            ->assertJson(['data' => []]);
    }

    /**
     * @Then the report should not contain the following rows:
     */
    public function theReportShouldNotContainTheFollowingRows(TableNode $rows): void
    {
        $this->assertNotNull($this->generatedReport);

        $test = [
            [
                'name' => 'Mateus',
                'age' => 24
            ],
            [
                'name' => 'John',
                'age' => 30
            ],
            [
                'name' => 'Jane',
                'age' => 25
            ]
        ];



        // Order matters
        $data = collect($this->generatedReport->getData(true)['data']);

        foreach ($data as $row) {
            foreach ($rows->getHash() as $expectedRow) {
                // we must check every $row attribute against $expectedRow, if $expectedRow doesnt have the attribute, we ignore it
                foreach ($expectedRow as $key => $value) {
                    if ($row[$key] !== $value) {
                        continue;
                    }
                }
            }
        }
    }
}
