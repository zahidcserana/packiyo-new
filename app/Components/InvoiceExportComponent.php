<?php

namespace App\Components;

use App\Jobs\Billing\GenerateCsvJob;
use App\Models\{BulkInvoiceBatch,
    Customer,
    CustomerSetting,
    Invoice,
    BillingRate,
    PurchaseOrder,
    ShippingCarrier,
    ShippingMethod,
    UserSetting};
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\{Facades\File, Facades\Storage};
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceExportComponent extends BaseComponent
{
    private function getCsvHeaders($filename): array
    {
        return [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];
    }

    public function generateCsv(Invoice $invoice): RedirectResponse
    {
        $this->deleteGeneratedCsv($invoice);
        $fileInfo = $this->getFileInfo($invoice);

        GenerateCsvJob::dispatch($invoice, $fileInfo);

        return redirect()->back();
    }

    public function getFileInfo(Invoice|BulkInvoiceBatch $data): array
    {
        return [
            'filename' => $this->composeCsvFileName($data),
            'dateFormat' => user_settings(UserSetting::USER_SETTING_DATE_FORMAT)
        ];
    }

    public function composeCsvFileName(Invoice|BulkInvoiceBatch $data): string
    {
        return $data->customer->contactInformation->name
            . ' '
            . localized_date($data->period_start)
            . ' - '
            . localized_date($data->period_end)
            . '.csv';
    }

    public function downloadGeneratedCsv(Invoice $invoice): StreamedResponse
    {
        return Storage::download($invoice->csv_url);
    }

    public function deleteGeneratedCsv(Invoice $invoice)
    {
        $privatePath = storage_path() . '/app/' . $invoice->csv_url;

        if (File::exists($privatePath)) {
            File::delete($privatePath);

            $invoice->csv_url = null;
            $invoice->save();
        }
    }

    public function exportStreamToCsv(Invoice $invoice, array|null $fileInfo = null): void
    {
        ob_start();
        app('invoiceExport')->exportToCsv($invoice, $fileInfo)->sendContent();
        $invoice->csv_url = 'private/csv/' . $fileInfo['filename'];
        Storage::put($invoice->csv_url, ob_get_clean());
        $invoice->save();
    }

    public function exportToCsv(Invoice $invoice, $fileInfo = null): StreamedResponse
    {
        if (empty($fileInfo)) {
            $fileInfo = $this->getFileInfo($invoice);
        }

        $fileName = $fileInfo['filename'];
        $invoiceLineItems = $invoice->invoiceLineItems()->with(
            'packageItem',
            'invoice',
            'billingRate',
            'shipment'
        )->get();
        $shippingCarriers = ShippingCarrier::pluck('name', 'id');
        $shippingMethods = ShippingMethod::pluck('name', 'id');
        $headers = $this->getCsvHeaders($fileName);
        $weightUnit = customer_settings($invoice->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, Customer::WEIGHT_UNIT_DEFAULT);
        $callback = $this->getCallbackForCsv(
            $invoiceLineItems,
            $this->getColumns($weightUnit),
            $shippingCarriers,
            $shippingMethods,
            $fileInfo
        );

        return response()->stream($callback, 200, $headers);
    }

    public function exportBatchInvoiceToCsv(BulkInvoiceBatch $bulkInvoiceBatch)
    {
        if (empty($fileInfo)) {
            $fileInfo = $this->getFileInfo($bulkInvoiceBatch);
        }

        $fileName = $fileInfo['filename'];
        $headers = $this->getCsvHeaders($fileName);
        $shippingCarriers = ShippingCarrier::pluck('name', 'id');
        $shippingMethods = ShippingMethod::pluck('name', 'id');
        $weightUnit = customer_settings($bulkInvoiceBatch->customer->id, CustomerSetting::CUSTOMER_SETTING_WEIGHT_UNIT, Customer::WEIGHT_UNIT_DEFAULT);

        $invoices = $bulkInvoiceBatch->bulkInvoiceBatchInvoices->map(function($bulkInvoiceBatchInvoice) {
            return $bulkInvoiceBatchInvoice->invoice;
        });

        $invoiceLineItems = collect();
        /** @var Invoice $invoice */
        foreach ($invoices as $invoice) {
            if(!$invoice->invoiceLineItems()->exists()) {
                continue;
            }

            $invoiceLineItems = $invoiceLineItems->merge($invoice->invoiceLineItems()->with(
                'packageItem',
                'invoice',
                'billingRate',
                'shipment'
            )->get());
        }

        if($invoiceLineItems->isEmpty()) {
           return redirect()->back()->withErrors(__('Invoice does not have any invoice line items.'));
        }

        $callback = $this->getCallbackForCsv($invoiceLineItems, $this->getColumns($weightUnit), $shippingCarriers, $shippingMethods, $fileInfo);

        return response()->stream($callback, 200, $headers);
    }

    public function exportInvoiceLinesToCsv($period, $invoices)
    {
        $periodFormatted = str_replace(array("-", " "), array('', ""), $period);
        $filename = $periodFormatted . '-invoice-lines.csv';

        $headers = $this->getCsvHeaders($filename);

        $columns = [
            'ParentKey',
            'LineNum',
            'ItemCode',
            'ItemDescription',
            'Quantity',
            'ShipDate',
            'Price',
            'PriceAfterVAT',
            'Currency',
            'Rate',
            'DiscountPercent',
            'VendorNum',
            'SerialNum',
            'WarehouseCode',
            'SalesPersonCode',
            'CommisionPercent',
            'TreeType',
            'AccountCode',
            'UseBaseUnits',
            'SupplierCatNum',
            'CostingCode',
            'ProjectCode',
            'BarCode',
            'VatGroup',
            'Height1',
            'Hight1Unit',
            'Height2',
            'Height2Unit',
            'Lengh1',
            'Lengh1Unit',
            'Lengh2',
            'Lengh2Unit',
            'Weight1',
            'Weight1Unit',
            'Weight2',
            'Weight2Unit',
            'Factor1',
            'Factor2',
            'Factor3',
            'Factor4',
            'BaseType',
            'BaseEntry',
            'BaseLine',
            'Volume',
            'VolumeUnit',
            'Width1',
            'Width1Unit',
            'Width2',
            'Width2Unit',
            'Address',
            'TaxCode',
            'TaxType',
            'TaxLiable',
            'BackOrder',
            'FreeText',
            'ShippingMethod',
            'CorrectionInvoiceItem',
            'CorrInvAmountToStock',
            'CorrInvAmountToDiffAcct',
            'WTLiable',
            'DeferredTax',
            'MeasureUnit',
            'UnitsOfMeasurment',
            'LineTotal',
            'TaxPercentagePerRow',
            'TaxTotal',
            'ConsumerSalesForecast',
            'ExciseAmount',
            'CountryOrg',
            'SWW',
            'TransactionType',
            'DistributeExpense',
            'RowTotalFC',
            'CFOPCode',
            'CSTCode',
            'Usage',
            'TaxOnly',
            'UnitPrice',
            'LineStatus',
            'PackageQuantity',
            'LineType',
            'COGSCostingCode',
            'COGSAccountCode',
            'ChangeAssemlyBoMWarehouse',
            'GrossBuyPrice',
            'GrossBase',
            'GrossProfitTotalBasePrice',
            'CostingCode2',
            'CostingCode3',
            'CostingCode4',
            'CostingCode5',
            'ItemDetails',
            'LocationCode',
            'ActualDeliveryDate',
            'ExLineNo',
            'RequiredDate',
            'RequiredQuantity',
            'COGSCostingCode2',
            'COGSCostingCode3',
            'COGSCostingCode4',
            'COGSCostingCode5',
            'CSTforIPI',
            'CSTforPIS',
            'CSTforCOFINS',
            'CreditOriginCode',
            'WithoutInventoryMovement',
            'AgreementNo',
            'AgreementRowNumber',
            'ActualBaseEntry',
            'ActualBaseLine',
            'DocEntry',
            'Surpluses',
            'DefectAndBreakup',
            'Shortages',
            'ConsiderQuantity',
            'PartialRetirement',
            'RetirementQuantity',
            'RetirementAPC',
            'ThirdParty',
            'ExpenseType',
            'ReceiptNumber',
            'ExpenseOperationType',
            'FederalTaxID',
            'UoMEntry',
            'InventoryQuantity',
            'ParentLineNum',
            'Incoterms',
            'TransportMode',
            'ChangeInventoryQuantityIndependently',
            'FreeOfChargeBP',
            'SACEntry',
            'HSNEntry',
            'GrossPrice',
            'GrossTotal',
            'GrossTotalFC',
            'NCMCode',
            'ShipToCode',
            'ShipToDescription'
        ];

        $firstRow = [
            'DocNum',
            'LineNum',
            'ItemCode',
            'Dscription',
            'Quantity',
            'ShipDate',
            'Price',
            'PriceAfVAT',
            'Currency',
            'Rate',
            'DiscPrcnt',
            'VendorNum',
            'SerialNum',
            'WhsCode',
            'SlpCode',
            'Commission',
            'TreeType',
            'AcctCode',
            'UseBaseUn',
            'SubCatNum',
            'OcrCode',
            'Project',
            'CodeBars',
            'VatGroup',
            'Height1',
            'Hight1Unit',
            'Height2',
            'Hght2Unit',
            'Length1',
            'Len1Unit',
            'length2',
            'Len2Unit',
            'Weight1',
            'Wght1Unit',
            'Weight2',
            'Wght2Unit',
            'Factor1',
            'Factor2',
            'Factor3',
            'Factor4',
            'BaseType',
            'BaseEntry',
            'BaseLine',
            'Volume',
            'VolUnit',
            'Width1',
            'Wdth1Unit',
            'Width2',
            'Wdth2Unit',
            'Address',
            'TaxCode',
            'TaxType',
            'TaxStatus',
            'BackOrdr',
            'FreeTxt',
            'TrnsCode',
            'CEECFlag',
            'ToStock',
            'ToDiff',
            'WtLiable',
            'DeferrTax',
            'unitMsr',
            'NumPerMsr',
            'LineTotal',
            'VatPrcnt',
            'VatSum',
            'ConsumeFCT',
            'ExciseAmt',
            'CountryOrg',
            'SWW',
            'TranType',
            'DistribExp',
            'TotalFrgn',
            'CFOPCode',
            'CSTCode',
            'Usage',
            'TaxOnly',
            'PriceBefDi',
            'LineStatus',
            'PackQty',
            'LineType',
            'CogsOcrCod',
            'CogsAcct',
            'ChgAsmBoMW',
            'GrossBuyPr',
            'GrossBase',
            'GPTtlBasPr',
            'OcrCode2',
            'OcrCode3',
            'OcrCode4',
            'OcrCode5',
            'Text',
            'LocCode',
            'ActDelDate',
            'ExLineNo',
            'PQTReqDate',
            'PQTReqQty',
            'CogsOcrCo2',
            'CogsOcrCo3',
            'CogsOcrCo4',
            'CogsOcrCo5',
            'CSTfIPI',
            'CSTfPIS',
            'CSTfCOFINS',
            'CredOrigin',
            'NoInvtryMv',
            'AgrNo',
            'AgrLnNum',
            'ActBaseEnt',
            'ActBaseLn',
            'DocEntry',
            'Surpluses',
            'DefBreak',
            'Shortages',
            'NeedQty',
            'PartRetire',
            'RetireQty',
            'RetireAPC',
            'ThirdParty',
            'ExpType',
            'ExpUUID',
            'ExpOpType',
            'LicTradNum',
            'UomEntry',
            'InvQty',
            'PrntLnNum',
            'Incoterms',
            'TransMod',
            'InvQtyOnly',
            'FreeChrgBP',
            'SacEntry',
            'HsnEntry',
            'GPBefDisc',
            'GTotal',
            'GTotalFC',
            'NCMCode',
            'ShipToCode',
            'ShipToDesc'
        ];

        $callback = function () use ($invoices, $columns, $firstRow) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $firstRow);

            foreach ($invoices as $invoice) {

                $vat = $invoice->customer->use_vat ? 1 + $invoice->customer->vat : 1;
                $linenum = 0;

                $invoiceLineItems = $invoice->invoiceLineItems->groupBy('billing_rate_id');
                foreach ($invoiceLineItems as $billing_rate_id => $items) {

                    $price = $items->sum('total_charge');
                    $priceVat = $price * $vat;
                    $billingRate = BillingRate::find($billing_rate_id);

                    fputcsv(
                        $file,
                        [
                            substr($invoice->invoice_number, -5),
                            $linenum++,
                            $billingRate->code ?? __('Rate code not set on Customer: ' . $invoice->customer->contactInformation->name),
                            '',
                            1,
                            '',
                            $price,
                            $priceVat,
                            $invoice->customer->currency->code ?? __('Currency not set on Customer: ' . $invoice->customer->contactInformation->name),
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $invoice->customer->vat_group
                        ]
                    );
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportInvoiceHeaderToCsv($period, $invoices)
    {
        $periodFormatted = str_replace(array("-", " "), array('', ""), $period);
        $filename = $periodFormatted . '-invoice-header.csv';

        $headers = $this->getCsvHeaders($filename);

        $columns = [
            'DocNum',
            'DocEntry',
            'DocType',
            'HandWritten',
            'Printed',
            'DocDate',
            'DocDueDate',
            'CardCode',
            'CardName',
            'Address',
            'NumAtCard',
            'Tax',
            'DocTotal',
            'AttachmentEntry',
            'DocCurrency',
            'DocRate',
            'Reference1',
            'Reference2',
            'Comments',
            'JournalMemo',
            'PaymentGroupCode',
            'DocTime',
            'SalesPersonCode',
            'TransportationCode',
            'Confirmed',
            'ImportFileNum',
            'SummeryType',
            'ContactPersonCode',
            'ShowSCN',
            'Series',
            'TaxDate',
            'PartialSupply',
            'DocObjectCode',
            'ShipToCode',
            'Indicator',
            'FederalTaxID',
            'DiscountPercent',
            'PaymentReference',
            'DocTotalFc',
            'Form1099',
            'Box1099',
            'RevisionPo',
            'RequriedDate',
            'CancelDate',
            'BlockDunning',
            'Pick',
            'PaymentMethod',
            'PaymentBlock',
            'PaymentBlockEntry',
            'CentralBankIndicator',
            'MaximumCashDiscount',
            'Project',
            'ExemptionValidityDateFrom',
            'ExemptionValidityDateTo',
            'WareHouseUpdateType',
            'Rounding',
            'ExternalCorrectedDocNum',
            'InternalCorrectedDocNum',
            'DeferredTax',
            'TaxExemptionLetterNum',
            'AgentCode',
            'NumberOfInstallments',
            'ApplyTaxOnFirstInstallment',
            'VatDate',
            'DocumentsOwner',
            'FolioPrefixString',
            'FolioNumber',
            'DocumentSubType',
            'BPChannelCode',
            'BPChannelContact',
            'Address2',
            'PayToCode',
            'ManualNumber',
            'UseShpdGoodsAct',
            'IsPayToBank',
            'PayToBankCountry',
            'PayToBankCode',
            'PayToBankAccountNo',
            'PayToBankBranch',
            'BPL_IDAssignedToInvoice',
            'DownPayment',
            'ReserveInvoice',
            'LanguageCode',
            'TrackingNumber',
            'PickRemark',
            'ClosingDate',
            'SequenceCode',
            'SequenceSerial',
            'SeriesString',
            'SubSeriesString',
            'SequenceModel',
            'UseCorrectionVATGroup',
            'DownPaymentAmount',
            'DownPaymentPercentage',
            'DownPaymentType',
            'DownPaymentAmountSC',
            'DownPaymentAmountFC',
            'VatPercent',
            'ServiceGrossProfitPercent',
            'OpeningRemarks',
            'ClosingRemarks',
            'RoundingDiffAmount',
            'ControlAccount',
            'InsuranceOperation347',
            'ArchiveNonremovableSalesQuotation',
            'GTSChecker',
            'GTSPayee',
            'ExtraMonth',
            'ExtraDays',
            'CashDiscountDateOffset',
            'StartFrom',
            'NTSApproved',
            'ETaxWebSite',
            'ETaxNumber',
            'NTSApprovedNumber',
            'EDocGenerationType',
            'EDocSeries',
            'EDocNum',
            'EDocExportFormat',
            'EDocStatus',
            'EDocErrorCode',
            'EDocErrorMessage',
            'DownPaymentStatus',
            'GroupSeries',
            'GroupNumber',
            'GroupHandWritten',
            'ReopenOriginalDocument',
            'ReopenManuallyClosedOrCanceledDocument',
            'CreateOnlineQuotation',
            'POSEquipmentNumber',
            'POSManufacturerSerialNumber',
            'POSCashierNumber',
            'ApplyCurrentVATRatesForDownPaymentsToDraw',
            'ClosingOption',
            'SpecifiedClosingDate',
            'OpenForLandedCosts',
            'RelevantToGTS',
            'AnnualInvoiceDeclarationReference',
            'Supplier',
            'Releaser',
            'Receiver',
            'BlanketAgreementNumber',
            'IsAlteration',
            'AssetValueDate',
            'DocumentDelivery',
            'AuthorizationCode',
            'StartDeliveryDate',
            'StartDeliveryTime',
            'EndDeliveryDate',
            'EndDeliveryTime',
            'VehiclePlate',
            'ATDocumentType',
            'ElecCommStatus',
            'ReuseDocumentNum',
            'ReuseNotaFiscalNum',
            'PrintSEPADirect',
            'FiscalDocNum',
            'POSDailySummaryNo',
            'POSReceiptNo',
            'PointOfIssueCode',
            'Letter',
            'FolioNumberFrom',
            'FolioNumberTo',
            'InterimType',
            'RelatedType',
            'RelatedEntry',
            'SAPPassport',
            'DocumentTaxID',
            'DateOfReportingControlStatementVAT',
            'ReportingSectionControlStatementVAT',
            'ExcludeFromTaxReportControlStatementVAT',
            'POS_CashRegister',
            'PriceMode',
            'Revision',
            'OriginalRefNo',
            'OriginalRefDate',
            'GSTTransactionType',
            'OriginalCreditOrDebitNo',
            'OriginalCreditOrDebitDate',
            'ECommerceOperator',
            'ECommerceGSTIN',
            'ShipFrom',
            'CommissionTrade',
            'CommissionTradeReturn',
            'UseInvoiceToAddrToDetermineTax',
            'IssuingReason',
            'Cig',
            'Cup',
            'EDocType'
        ];

        $firstRow = [
            'DocNum',
            'DocEntry',
            'DocType',
            'Handwrtten',
            'Printed',
            'DocDate',
            'DocDueDate',
            'CardCode',
            'CardName',
            'Address',
            'NumAtCard',
            'Tax',
            'DocTotal',
            'AtcEntry',
            'DocCur',
            'DocRate',
            'Ref1',
            'Ref2',
            'Comments',
            'JrnlMemo',
            'GroupNum',
            'DocTime',
            'SlpCode',
            'TrnspCode',
            'Confirmed',
            'ImportEnt',
            'SummryType',
            'CntctCode',
            'ShowSCN',
            'Series',
            'TaxDate',
            'PartSupply',
            'ObjType',
            'ShipToCode',
            'Indicator',
            'LicTradNum',
            'DiscPrcnt',
            'PaymentRef',
            'DocTotalFC',
            'Form1099',
            'Box1099',
            'RevisionPo',
            'ReqDate',
            'CancelDate',
            'BlockDunn',
            'Pick',
            'PeyMethod',
            'PayBlock',
            'PayBlckRef',
            'CntrlBnk',
            'MaxDscn',
            'Project',
            'FromDate',
            'ToDate',
            'UpdInvnt',
            'Rounding',
            'CorrExt',
            'CorrInv',
            'DeferrTax',
            'LetterNum',
            'AgentCode',
            'Installmnt',
            'VATFirst',
            'VatDate',
            'OwnerCode',
            'FolioPref',
            'FolioNum',
            'DocSubType',
            'BPChCode',
            'BPChCntc',
            'Address2',
            'PayToCode',
            'ManualNum',
            'UseShpdGd',
            'IsPaytoBnk',
            'BnkCntry',
            'BankCode',
            'BnkAccount',
            'BnkBranch',
            'BPLId',
            'DpmPrcnt',
            'isIns',
            'LangCode',
            'TrackNo',
            'PickRmrk',
            'ClsDate',
            'SeqCode',
            'Serial',
            'SeriesStr',
            'SubStr',
            'Model',
            'UseCorrVat',
            'DpmAmnt',
            'DpmPrcnt',
            'Posted',
            'DpmAmntSC',
            'DpmAmntFC',
            'VatPercent',
            'SrvGpPrcnt',
            'Header',
            'Footer',
            'RoundDif',
            'CtlAccount',
            'InsurOp347',
            'IgnRelDoc',
            'Checker',
            'Payee',
            'ExtraMonth',
            'ExtraDays',
            'CdcOffset',
            'PayDuMonth',
            'NTSApprov',
            'NTSWebSite',
            'NTSeTaxNo',
            'NTSApprNo',
            'EDocGenTyp',
            'ESeries',
            'EDocNum',
            'EDocExpFrm',
            'EDocStatus',
            'EDocErrCod',
            'EDocErrMsg',
            'DpmStatus',
            'PQTGrpSer',
            'PQTGrpNum',
            'PQTGrpHW',
            'ReopOriDoc',
            'ReopManCls',
            'OnlineQuo',
            'POSEqNum',
            'POSManufSN',
            'POSCashN',
            'DpmAsDscnt',
            'ClosingOpt',
            'SpecDate',
            'OpenForLaC',
            'GTSRlvnt',
            'AnnInvDecR',
            'Supplier',
            'Releaser',
            'Receiver',
            'AgrNo',
            'IsAlt',
            'AssetDate',
            'DocDlvry',
            'AuthCode',
            'StDlvDate',
            'StDlvTime',
            'EndDlvDate',
            'EndDlvTime',
            'VclPlate',
            'AtDocType',
            'ElCoStatus',
            'IsReuseNum',
            'IsReuseNFN',
            'PrintSEPA',
            'FiscDocNum',
            'ZrdAbs',
            'POSRcptNo',
            'PTICode',
            'Letter',
            'FolNumFrom',
            'FolNumTo',
            'InterimTyp',
            'RelatedTyp',
            'RelatedEnt',
            'SAPPassprt',
            'DocTaxID',
            'DateReport',
            'RepSection',
            'ExclTaxRep',
            'PosCashReg',
            'PriceMode',
            'Revision',
            'RevRefNo',
            'RevRefDate',
            'GSTTranTyp',
            'RevCreRefN',
            'RevCreRefD',
            'ECommerBP',
            'EComerGSTN',
            'ShipToCode',
            'ComTrade',
            'ComTradeRt',
            'UseBilAddr',
            'IssReason',
            'CIG',
            'CUP',
            'EDocType'
        ];

        $callback = function () use ($invoices, $columns, $firstRow) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $firstRow);

            foreach ($invoices as $invoice) {
                $subtotal = $invoice->invoiceLineItems->sum('total_charge');
                $missingMessage = 'Customer is probably deleted (missing related data. Customer: ' . $invoice->customer_id . ' / Invoice: ' . $invoice->id . ').';

                $total = $invoice->customer ? ($subtotal * ($invoice->customer->vat + 1)) : $missingMessage;

                fputcsv(
                    $file,
                    [
                        $invoice->invoice_number,
                        '',
                        'dDocument_Items',
                        'tYES',
                        '',
                        $invoice->period_end->format('Ymd'),
                        '',
                        $invoice->customer ? $invoice->customer->code : $missingMessage,
                        '',
                        '',
                        '',
                        $invoice->customer ? $total - $subtotal : $missingMessage,
                        $total,
                        '',
                        $invoice->customer ? ($invoice->customer->currency->code ?? __('Currency not set on Customer: ' . $invoice->customer->contactInformation->name)) : $missingMessage,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        $invoice->period_end->format('Ymd')
                    ]
                );
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getInvoiceLineItemShipment($item)
    {
        return match (!null) {
            $item->shipment => $item->shipment,
            $item->packageItem => $item->packageItem->package->shipment,
            $item->package => $item->package->shipment,
            default => null,
        };
    }

    private function getInvoiceLineItemOrder($item)
    {
        return match (!null) {
            $item->shipment => $item->shipment->order,
            $item->packageItem => $item->packageItem->package->shipment->order,
            $item->package => $item->package->shipment->order,
            $item->returnItem => $item->returnItem->return_->order,
            $item->purchaseOrder => $item->purchaseOrder,
            $item->purchaseOrderItem => $item->purchaseOrderItem->purchaseOrder,
            default => null,
        };
    }

    private function getShippingMethods($item, $shippingMethods)
    {
        $methods = '';

        if (array_key_exists('shipping_method', $item->billingRate->settings)) {
            foreach ($item->billingRate->settings['shipping_method'] as $index => $shippingMethodId) {
                $methods .= $shippingMethods[$shippingMethodId];

                if ($index + 1 !== count($item->billingRate->settings['shipping_method'])) {
                    $methods .= ', ';
                }
            }
        } else {
            $methods = $shippingMethods[$item->billingRate->settings['shipping_method_id']];
        }


        return $methods;
    }

    /**
     * @param Collection $invoiceLineItems
     * @param array $columns
     * @param $shippingCarriers
     * @param $shippingMethods
     * @param mixed $fileInfo
     * @return Closure
     */
    private function getCallbackForCsv(Collection $invoiceLineItems, array $columns, $shippingCarriers, $shippingMethods, mixed $fileInfo): Closure
    {
        return function () use ($invoiceLineItems, $columns, $shippingCarriers, $shippingMethods, $fileInfo) {
            #Writes file
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoiceLineItems as $item) {
                $product = $item->packageItem->orderItem ?? null;
                $shipment = $this->getInvoiceLineItemShipment($item);
                $order = $this->getInvoiceLineItemOrder($item);
                $shipmentTracking = $shipment
                    ? (
                    $shipment->shipmentTrackings->count()
                        ? $shipment->shipmentTrackings->first()->tracking_number
                        : ''
                    )
                    : '';
                $orderNumber = $order
                    ? (
                    $order instanceof PurchaseOrder
                        ? '[PO] ' . $order->number
                        : $order->number
                    )
                    : '';
                $localizedPeriodStart = $item->invoice->period_start
                    ? localized_date($item->invoice->period_start)
                    : '';
                $localizedPeriodEnd = $item->invoice->period_end
                    ? localized_date($item->invoice->period_end)
                    : '';
                $shipmentContactName = $shipment ? $shipment->contactInformation->name : '';
                $shipmentContactAddress = $shipment ? $shipment->contactInformation->address : '';
                $shipmentContactCountry = $shipment
                    ? (
                    $shipment->contactInformation->country
                        ? $shipment->contactInformation->country->title
                        : 'no country'
                    )
                    : '';
                $shippingCarrierName = array_key_exists('shipping_carrier_id', $item->billingRate->settings)
                    ? $shippingCarriers[$item->billingRate->settings['shipping_carrier_id']]
                    : '';
                $shippingMethodName = array_key_exists('shipping_method', $item->billingRate->settings)
                || array_key_exists('shipping_method_id', $item->billingRate->settings)
                    ? $this->getShippingMethods($item, $shippingMethods)
                    : '';
                $shipmentTotalWeight = $shipment ? $shipment->getTotalWeight() : '';
                $shipmentTotalQuantity = $shipment ? $shipment->shipmentItems->sum('quantity') : '';
                $shipmentDate = $shipment ? $shipment->shipped_at->format($fileInfo['dateFormat']) : '';

                $trackingNumber = $item->shipment?->getFirstTrackingNumber() ?? '';
                $order = $item->shipment?->order->number;

                fputcsv(
                    $file,
                    [
                        $item->billingRate->type ?? '', //                           #1
                        $item->billingRate->name ?? '', //                           #2
                        $item->description ?? '', //                                 #3
                        $item->quantity ?? '', //                                    #4
                        $item->charge_per_unit ?? '', //                             #5
                        $item->total_charge ?? '', //                                #6
                        $item->period_end ?? '', //                                  #7

                        $product->sku ?? '', //                                      #8
                        $product->name ?? '', //                                     #9
                        $product->weight ?? '', //                                   #10
                        $product->height ?? '', //                                   #11
                        $product->length ?? '', //                                   #12
                        $product->width ?? '', //                                    #13

                        // 14 to 20 refer to returns and inventory changes, for billing rates not yet ported.

                        $shipmentTracking ?? '', //                                  #21

                        $item->invoice->customer->contactInformation->name ?? '', // #22
                        $item->invoice->id ?? '', //                                 #23
                        $localizedPeriodStart, //                                    #24
                        $localizedPeriodEnd, //                                      #25

                        $item->invoice->customer->contactInformation->name ?? '', // #26
                        $orderNumber, //                                             #27
                        $shipmentContactName, //                                     #28
                        $shipmentContactAddress, //                                  #29
                        $shipmentContactCountry, //                                  #30
                        $shippingCarrierName, //                                     #31
                        $shippingMethodName, //                                      #32
                        $shipmentTotalWeight, //                                     #33
                        $shipment->tracking_code ?? '', //                           #34
                        $shipmentTotalQuantity, //                                   #35
                        $shipmentDate, //                                            #36
                        $order ?? '', //                                             #37
                        $trackingNumber ?? '', //                                    #38
                    ]
                );
            }

            fclose($file);
        };
    }

    /**
     * @param mixed $weightUnit
     * @return string[]
     */
    private function getColumns(mixed $weightUnit): array
    {
        return [
            'Type (charge/invoice item)', //            #1
            'Name (charge/invoice item)', //            #2
            'Description (charge/invoice item)', //     #3
            'Quantity (charge/invoice item)', //        #4
            'Unit Price (charge/invoice item)', //      #5
            'Total Price (charge/invoice item)', //     #6
            'Item Period end (charge/invoice item)', // #7

            'SKU (Product)', //                         #8
            'Name (Product)', //                        #9
            'Weight (Product)', //                      #10
            'Height (Product)', //                      #11
            'Length (Product)', //                      #12
            'Width (Product)', //                       #13

            // 14 to 20 refer to returns and inventory changes, for billing rates not yet ported.

            'Shipment', //                              #21

            'Customer', //                              #22
            'Invoice id', //                            #23
            'Invoice Period Start', //                  #24
            'Invoice Period End', //                    #25

            'Client Name', //                           #26
            'Client Order Reference', //                #27
            'Delivery Name', //                         #28
            'Delivery Address', //                      #29
            'Country', //                               #30
            'Carrier', //                               #31
            'Service', //                               #32
            'Total weight (' . $weightUnit . ')', //    #33
            'Tracking Number', //                       #34
            'Number of units in shipment', //           #35
            'Date Dispatched', //                       #36
            'Order No', //                              #37
            'Tracking No', //                           #38
        ];
    }
}
