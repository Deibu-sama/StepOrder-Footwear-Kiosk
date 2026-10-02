<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\FirestoreService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly ActivityLogService $activity,
        private readonly SettingsService $settings
    ) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $data = $this->buildReportData($filters);

        return view('admin.reports.index', [
            'filters' => $filters,
            'data' => $data,
            'settings' => $this->settings->all(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $data = $this->buildReportData($filters);
        $settings = $this->settings->all();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->buildSummarySheet($spreadsheet, $data, $filters, $settings);
        $this->buildDailySalesSheet($spreadsheet, $data);
        $this->buildProductSalesSheet($spreadsheet, $data);
        $this->buildOrderDetailsSheet($spreadsheet, $data, $settings);
        $this->buildInventorySheet($spreadsheet, $data, $settings);
        $this->buildActivitySheet($spreadsheet, $data, $settings);

        $spreadsheet->setActiveSheetIndex(0);

        $fromLabel = $filters['from'] ?: 'all';
        $toLabel = $filters['to'] ?: 'today';
        $filename = 'StepOrder_Report_'.$fromLabel.'_to_'.$toLabel.'.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    private function filters(Request $request): array
    {
        $from = trim($request->string('from')->toString());
        $to = trim($request->string('to')->toString());
        $status = trim($request->string('status')->toString());

        if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = '';
        }

        if ($to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = '';
        }

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        if (!in_array($status, ['', 'all', 'pending', 'paid', 'completed', 'cancelled'], true)) {
            $status = '';
        }

        return compact('from', 'to', 'status');
    }

    private function buildReportData(array $filters): array
    {
        $settings = $this->settings->all();
        $orders = $this->firestore->list('orders');
        $products = $this->firestore->list('products');
        $logs = $this->activity->list();

        $filteredOrders = array_values(array_filter(
            $orders,
            fn ($order) => $this->inDateRange($order['created_at'] ?? null, $filters)
                && ($filters['status'] === '' || $filters['status'] === 'all' || ($order['status'] ?? 'pending') === $filters['status'])
        ));

        $salesOrders = array_values(array_filter(
            $filteredOrders,
            fn ($order) => in_array(($order['status'] ?? ''), ['paid', 'completed'], true)
        ));

        $completedOrders = array_values(array_filter(
            $filteredOrders,
            fn ($order) => ($order['status'] ?? '') === 'completed'
        ));

        $cancelledOrders = array_values(array_filter(
            $filteredOrders,
            fn ($order) => ($order['status'] ?? '') === 'cancelled'
        ));

        $pendingOrders = array_values(array_filter(
            $filteredOrders,
            fn ($order) => ($order['status'] ?? '') === 'pending'
        ));

        $paidOrders = array_values(array_filter(
            $filteredOrders,
            fn ($order) => ($order['status'] ?? '') === 'paid'
        ));

        $revenue = array_sum(array_map(
            fn ($order) => (float)($order['total'] ?? 0),
            $salesOrders
        ));

        $unitsSold = 0;
        $unitsReleased = 0;
        foreach ($salesOrders as $order) {
            $orderUnits = $this->orderUnits($order);

            $unitsSold += $orderUnits;

            if (($order['status'] ?? '') === 'completed') {
                $unitsReleased += $orderUnits;
            }
        }

        $avgOrder = count($salesOrders) > 0 ? $revenue / count($salesOrders) : 0;

        $daily = [];
        foreach ($filteredOrders as $order) {
            $status = $order['status'] ?? 'pending';
            $date = $this->orderDate($order['created_at'] ?? null);

            if (!$date) {
                continue;
            }

            if (!isset($daily[$date])) {
                $daily[$date] = [
                    'date' => $date,
                    'orders' => 0,
                    'sales_orders' => 0,
                    'revenue' => 0,
                    'units_sold' => 0,
                    'units_released' => 0,
                    'cancelled' => 0,
                ];
            }

            $daily[$date]['orders']++;

            if (in_array($status, ['paid', 'completed'], true)) {
                $daily[$date]['sales_orders']++;
                $daily[$date]['revenue'] += (float)($order['total'] ?? 0);
                $daily[$date]['units_sold'] += $this->orderUnits($order);
            }

            if ($status === 'completed') {
                $daily[$date]['units_released'] += $this->orderUnits($order);
            }

            if ($status === 'cancelled') {
                $daily[$date]['cancelled']++;
            }
        }

        ksort($daily);

        $productSales = [];
        foreach ($salesOrders as $order) {
            $released = ($order['status'] ?? '') === 'completed';

            foreach (($order['items'] ?? []) as $item) {
                $productKey = (string)($item['product_id'] ?? $item['sku'] ?? $item['name'] ?? 'unknown');
                $quantity = (int)($item['quantity'] ?? 0);
                $price = (float)($item['price'] ?? 0);
                $revenueAmount = $price * $quantity;

                if (!isset($productSales[$productKey])) {
                    $productSales[$productKey] = [
                        'product_id' => $item['product_id'] ?? '',
                        'sku' => $item['sku'] ?? '',
                        'name' => $item['name'] ?? 'Unknown Product',
                        'category' => $item['category_name'] ?? ($item['category'] ?? '—'),
                        'units_sold' => 0,
                        'units_released' => 0,
                        'revenue' => 0,
                    ];
                }

                $productSales[$productKey]['units_sold'] += $quantity;
                $productSales[$productKey]['revenue'] += $revenueAmount;

                if ($released) {
                    $productSales[$productKey]['units_released'] += $quantity;
                }
            }
        }

        $productSales = array_values($productSales);
        usort($productSales, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        $categoryMap = [];
        foreach ($products as $product) {
            $categoryMap[$product['id'] ?? ''] = $product['category_name'] ?? 'Uncategorized';
        }

        $inventory = [];
        $lowThreshold = (int)($settings['low_stock_threshold'] ?? 3);

        foreach ($products as $product) {
            $variants = is_array($product['variants'] ?? null) ? $product['variants'] : [];
            $totalUnits = 0;
            $lowCount = 0;
            $outCount = 0;

            foreach ($variants as $variant) {
                $stock = (int)($variant['stock'] ?? 0);
                $totalUnits += $stock;

                if ($stock === 0) {
                    $outCount++;
                } elseif ($stock <= $lowThreshold) {
                    $lowCount++;
                }
            }

            $inventory[] = [
                'product' => $product['name'] ?? 'Unnamed Product',
                'sku' => $product['sku'] ?? '',
                'category' => $product['category_name'] ?? 'Uncategorized',
                'gender' => $product['gender'] ?? 'Unisex',
                'status' => $product['status'] ?? 'active',
                'variants' => count($variants),
                'units' => $totalUnits,
                'low' => $lowCount,
                'out' => $outCount,
            ];
        }

        usort($inventory, fn ($a, $b) => $a['units'] <=> $b['units']);

        $filteredLogs = array_values(array_filter(
            $logs,
            fn ($log) => $this->inDateRange($log['created_at'] ?? null, $filters)
        ));

        return [
            'orders' => $filteredOrders,
            'sales_orders' => $salesOrders,
            'paid_orders' => $paidOrders,
            'completed_orders' => $completedOrders,
            'pending_orders' => $pendingOrders,
            'cancelled_orders' => $cancelledOrders,
            'revenue' => $revenue,
            'units_sold' => $unitsSold,
            'units_released' => $unitsReleased,
            'avg_order' => $avgOrder,
            'daily' => array_values($daily),
            'product_sales' => $productSales,
            'inventory' => $inventory,
            'logs' => $filteredLogs,
            'active_products' => count(array_filter($products, fn ($product) => ($product['status'] ?? 'active') === 'active')),
            'total_inventory_units' => array_sum(array_column($inventory, 'units')),
            'categories' => $categoryMap,
        ];
    }

    private function inDateRange(?string $value, array $filters): bool
    {
        $date = $this->orderDate($value);

        if (!$date) {
            return false;
        }

        if ($filters['from'] && $date < $filters['from']) {
            return false;
        }

        if ($filters['to'] && $date > $filters['to']) {
            return false;
        }

        return true;
    }

    private function orderDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone(config('app.timezone'))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function orderUnits(array $order): int
    {
        return array_sum(array_map(
            fn ($item) => (int)($item['quantity'] ?? 0),
            $order['items'] ?? []
        ));
    }

    private function createSpreadsheet(string $title): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('StepOrder')
            ->setTitle($title)
            ->setSubject('StepOrder Management Report')
            ->setDescription('Comprehensive operational report generated by StepOrder.');

        return $spreadsheet;
    }

    private function buildSummarySheet(Spreadsheet $spreadsheet, array $data, array $filters, array $settings): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Executive Summary');

        $accent = $settings['primary_color'] ?? '#BEF264';
        $accentStrong = $settings['primary_strong_color'] ?? '#65A30D';

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', $settings['brand_name'].' · Operations Report');
        $sheet->getStyle('A1:H1')->applyFromArray($this->titleStyle('#111111', '#FFFFFF', 22));
        $sheet->getRowDimension(1)->setRowHeight(34);

        $sheet->mergeCells('A2:H2');
        $periodLabel = $this->periodLabel($filters);
        $sheet->setCellValue('A2', 'Reporting period: '.$periodLabel.' · Generated '.now(config('app.timezone'))->format('M d, Y h:i A'));
        $sheet->getStyle('A2:H2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '666666']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $cards = [
            ['A4:B6', 'TOTAL ORDERS', count($data['orders']), '#E8F1FF'],
            ['C4:D6', 'NET SALES', $data['revenue'], '#E8FFE8'],
            ['E4:F6', 'UNITS SOLD', $data['units_sold'], '#F2FFD8'],
            ['G4:H6', 'AVG ORDER VALUE', $data['avg_order'], '#FFF4D6'],
        ];

        foreach ($cards as [$range, $label, $value, $fill]) {
            $sheet->mergeCells($range);
            [$start] = explode(':', $range);
            $sheet->setCellValue($start, $label."\n".(
                str_contains($label, 'SALES') || str_contains($label, 'VALUE')
                    ? ($settings['currency_symbol'] ?? '₱').number_format($value, 2)
                    : number_format($value)
            ));
            $sheet->getStyle($range)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => ltrim($fill, '#')]],
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '111111']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            ]);
        }

        $sheet->mergeCells('A8:D8');
        $sheet->setCellValue('A8', 'ORDER PIPELINE');
        $sheet->getStyle('A8:D8')->applyFromArray($this->sectionStyle($accent));

        $pipeline = [
            ['Pending', count($data['pending_orders'])],
            ['Paid / Awaiting Release', count($data['paid_orders'])],
            ['Released / Completed', count($data['completed_orders'])],
            ['Cancelled', count($data['cancelled_orders'])],
        ];
        $sheet->fromArray($pipeline, null, 'A9');
        $this->styleSmallTable($sheet, 'A9:B13', $accentStrong);

        $sheet->mergeCells('F8:H8');
        $sheet->setCellValue('F8', 'INVENTORY SNAPSHOT');
        $sheet->getStyle('F8:H8')->applyFromArray($this->sectionStyle($accent));

        $inventorySummary = [
            ['Active products', $data['active_products']],
            ['Units in stock', $data['total_inventory_units']],
            ['Low-stock variants', array_sum(array_column($data['inventory'], 'low'))],
            ['Out-of-stock variants', array_sum(array_column($data['inventory'], 'out'))],
        ];
        $sheet->fromArray($inventorySummary, null, 'F9');
        $this->styleSmallTable($sheet, 'F9:G13', $accentStrong);

        $sheet->mergeCells('A15:H15');
        $sheet->setCellValue('A15', 'TOP PRODUCTS BY SALES');
        $sheet->getStyle('A15:H15')->applyFromArray($this->sectionStyle($accent));

        $topRows = array_slice($data['product_sales'], 0, 10);
        $rows = [['Product', 'SKU', 'Units Sold', 'Units Released', 'Revenue']];
        foreach ($topRows as $row) {
            $rows[] = [$row['name'], $row['sku'], $row['units_sold'], $row['units_released'], $row['revenue']];
        }
        $sheet->fromArray($rows, null, 'A16');
        $this->styleTable($sheet, 'A16:E'.(15 + count($rows)), $accentStrong, ['E' => '#,##0.00']);
        $sheet->freezePane('A17');

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(17);
        $sheet->getColumnDimension('E')->setWidth(17);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->setShowGridlines(false);
    }

    private function buildDailySalesSheet(Spreadsheet $spreadsheet, array $data): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Sales by Day');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'Sales by Day');
        $sheet->getStyle('A1:G1')->applyFromArray($this->titleStyle('#111111', '#FFFFFF', 18));

        $rows = [['Date', 'All Orders', 'Sales Orders', 'Net Sales', 'Units Sold', 'Units Released', 'Cancelled Orders']];
        foreach ($data['daily'] as $day) {
            $rows[] = [
                Carbon::parse($day['date'])->startOfDay(),
                $day['orders'],
                $day['sales_orders'],
                $day['revenue'],
                $day['units_sold'],
                $day['units_released'],
                $day['cancelled'],
            ];
        }

        $end = max(2, count($rows));
        $sheet->fromArray($rows, null, 'A3');
        $this->styleTable($sheet, "A3:G{$end}", '#65A30D', ['A' => 'mmm d, yyyy', 'D' => '#,##0.00']);
        $sheet->freezePane('A4');
        $sheet->getColumnDimension('A')->setWidth(16);
        foreach (['B','C','E','F','G'] as $col) $sheet->getColumnDimension($col)->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->setShowGridlines(false);
    }

    private function buildProductSalesSheet(Spreadsheet $spreadsheet, array $data): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Product Sales');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'Product Sales Performance');
        $sheet->getStyle('A1:I1')->applyFromArray($this->titleStyle('#111111', '#FFFFFF', 18));

        $rows = [['Product', 'SKU', 'Category', 'Units Sold', 'Units Released', 'Revenue', 'Avg Price', 'Share of Sales', 'Rank']];
        $totalRevenue = max(0.01, (float)$data['revenue']);

        foreach ($data['product_sales'] as $index => $row) {
            $rows[] = [
                $row['name'],
                $row['sku'],
                $row['category'],
                $row['units_sold'],
                $row['units_released'],
                $row['revenue'],
                $row['units_sold'] > 0 ? $row['revenue'] / $row['units_sold'] : 0,
                $row['revenue'] / $totalRevenue,
                $index + 1,
            ];
        }

        $end = max(2, count($rows));
        $sheet->fromArray($rows, null, 'A3');
        $this->styleTable($sheet, "A3:I{$end}", '#65A30D', [
            'F' => '#,##0.00',
            'G' => '#,##0.00',
            'H' => '0.0%',
        ]);
        $sheet->freezePane('A4');
        $sheet->setAutoFilter("A3:I{$end}");
        $widths = ['A'=>30,'B'=>18,'C'=>20,'D'=>14,'E'=>17,'F'=>16,'G'=>14,'H'=>15,'I'=>10];
        foreach ($widths as $col=>$width) $sheet->getColumnDimension($col)->setWidth($width);
        $sheet->setShowGridlines(false);
    }

    private function buildOrderDetailsSheet(Spreadsheet $spreadsheet, array $data, array $settings): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Order Details');

        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'Order Details');
        $sheet->getStyle('A1:L1')->applyFromArray($this->titleStyle('#111111', '#FFFFFF', 18));

        $rows = [[
            'Order #', 'Created', 'Status', 'Total', 'Units', 'Items',
            'Paid At', 'Paid By', 'Released At', 'Released By', 'Cancelled At', 'Items Summary'
        ]];

        foreach ($data['orders'] as $order) {
            $items = $order['items'] ?? [];
            $summaryParts = [];
            foreach ($items as $item) {
                $summaryParts[] = ($item['name'] ?? 'Item').' × '.(int)($item['quantity'] ?? 0).
                    ' ['.($item['size'] ?? '—').' / '.($item['color'] ?? '—').']';
            }

            $rows[] = [
                $order['order_number'] ?? $order['id'] ?? '—',
                $order['created_at'] ?? '',
                strtoupper($order['status'] ?? 'pending'),
                (float)($order['total'] ?? 0),
                $this->orderUnits($order),
                count($items),
                $order['paid_at'] ?? '',
                $order['paid_by_email'] ?? '',
                $order['released_at'] ?? ($order['completed_at'] ?? ''),
                $order['released_by_email'] ?? '',
                $order['cancelled_at'] ?? '',
                implode(' | ', $summaryParts),
            ];
        }

        $end = max(2, count($rows));
        $sheet->fromArray($rows, null, 'A3');
        $this->styleTable($sheet, "A3:L{$end}", '#65A30D', ['D' => '#,##0.00']);
        $sheet->freezePane('A4');
        $sheet->setAutoFilter("A3:L{$end}");
        foreach (['A'=>14,'B'=>22,'C'=>14,'D'=>15,'E'=>10,'F'=>10,'G'=>22,'H'=>28,'I'=>22,'J'=>28,'K'=>22,'L'=>60] as $col=>$width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getStyle("L4:L{$end}")->getAlignment()->setWrapText(true);
        $sheet->setShowGridlines(false);
    }

    private function buildInventorySheet(Spreadsheet $spreadsheet, array $data, array $settings): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Inventory Snapshot');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'Inventory Snapshot');
        $sheet->getStyle('A1:I1')->applyFromArray($this->titleStyle('#111111', '#FFFFFF', 18));

        $rows = [['Product', 'SKU', 'Category', 'Gender', 'Status', 'Variants', 'Units in Stock', 'Low Variants', 'Out Variants']];
        foreach ($data['inventory'] as $row) {
            $rows[] = [
                $row['product'],
                $row['sku'],
                $row['category'],
                $row['gender'],
                strtoupper($row['status']),
                $row['variants'],
                $row['units'],
                $row['low'],
                $row['out'],
            ];
        }

        $end = max(2, count($rows));
        $sheet->fromArray($rows, null, 'A3');
        $this->styleTable($sheet, "A3:I{$end}", '#65A30D');
        $sheet->freezePane('A4');
        $sheet->setAutoFilter("A3:I{$end}");
        $sheet->getStyle("H4:I{$end}")->getFont()->setBold(true);
        $sheet->getStyle("H4:H{$end}")->getFont()->getColor()->setRGB('D97706');
        $sheet->getStyle("I4:I{$end}")->getFont()->getColor()->setRGB('DC2626');
        foreach (['A'=>28,'B'=>18,'C'=>20,'D'=>13,'E'=>13,'F'=>12,'G'=>16,'H'=>15,'I'=>14] as $col=>$width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->setShowGridlines(false);
    }

    private function buildActivitySheet(Spreadsheet $spreadsheet, array $data, array $settings): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Activity Log');

        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'Activity & Audit Log');
        $sheet->getStyle('A1:J1')->applyFromArray($this->titleStyle('#111111', '#FFFFFF', 18));

        $rows = [['Timestamp', 'Action', 'Actor Email', 'Actor Role', 'Order #', 'Units', 'Total', 'Items', 'Details', 'Was Paid']];
        foreach ($data['logs'] as $log) {
            $itemsSummary = [];
            foreach (($log['items'] ?? []) as $item) {
                $itemsSummary[] = ($item['name'] ?? 'Item').' × '.(int)($item['quantity'] ?? 0);
            }

            $rows[] = [
                $log['created_at'] ?? '',
                str_replace('_', ' ', $log['action'] ?? 'UNKNOWN'),
                $log['actor_email'] ?? 'System',
                $log['actor_role'] ?? 'system',
                $log['order_number'] ?? '',
                (int)($log['unit_count'] ?? 0),
                (float)($log['total'] ?? 0),
                implode(' | ', $itemsSummary),
                $log['details'] ?? '',
                !empty($log['was_paid']) ? 'YES' : 'NO',
            ];
        }

        $end = max(2, count($rows));
        $sheet->fromArray($rows, null, 'A3');
        $this->styleTable($sheet, "A3:J{$end}", '#65A30D', ['G' => '#,##0.00']);
        $sheet->freezePane('A4');
        $sheet->setAutoFilter("A3:J{$end}");
        foreach (['A'=>22,'B'=>22,'C'=>28,'D'=>14,'E'=>14,'F'=>10,'G'=>16,'H'=>45,'I'=>50,'J'=>12] as $col=>$width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getStyle("H4:I{$end}")->getAlignment()->setWrapText(true);
        $sheet->setShowGridlines(false);
    }

    private function titleStyle(string $fill, string $fontColor, int $size = 18): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => ltrim($fill, '#')]],
            'font' => ['bold' => true, 'size' => $size, 'color' => ['rgb' => ltrim($fontColor, '#')]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function sectionStyle(string $accent): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => ltrim($accent, '#')]],
            'font' => ['bold' => true, 'color' => ['rgb' => '111111']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
    }

    private function styleSmallTable(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range, string $accent): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $headerRow = substr($range, 0, 2);
        $sheet->getStyle($headerRow)->getFont()->setBold(true);
        $sheet->getStyle($headerRow)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($headerRow)->getFill()->getStartColor()->setRGB(ltrim($accent, '#'));
    }

    private function styleTable(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range, string $accent, array $formats = []): void
    {
        $parts = explode(':', $range);
        $startCell = $parts[0];
        $endCell = $parts[1];
        preg_match('/\d+/', $startCell, $startRowMatch);
        $startRow = (int)($startRowMatch[0] ?? 1);
        $headerRow = $startRow;

        $sheet->getStyle($range)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E1E1E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => false],
        ]);

        $sheet->getStyle($startCell.':'.$endCell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        $headerStart = preg_replace('/\d+/', (string)$headerRow, $startCell);
        $headerEnd = preg_replace('/\d+/', (string)$headerRow, $endCell);

        $sheet->getStyle($headerStart.':'.$headerEnd)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => ltrim($accent, '#')]],
            'font' => ['bold' => true, 'color' => ['rgb' => '111111']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);

        foreach ($formats as $column => $format) {
            $sheet->getStyle($column.($headerRow + 1).':'.$column.preg_replace('/^[A-Z]+/', '', $endCell))->getNumberFormat()->setFormatCode($format);
        }
    }

    private function periodLabel(array $filters): string
    {
        if ($filters['from'] && $filters['to']) {
            return Carbon::parse($filters['from'])->format('M d, Y').' – '.Carbon::parse($filters['to'])->format('M d, Y');
        }

        if ($filters['from']) {
            return 'From '.Carbon::parse($filters['from'])->format('M d, Y');
        }

        if ($filters['to']) {
            return 'Through '.Carbon::parse($filters['to'])->format('M d, Y');
        }

        return 'All available records';
    }
}
