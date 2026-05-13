<?php
/**
 * Download a sample .xlsx for Product Opening bulk import — full column layout matching product selection grid.
 */
session_start();

if (empty($_SESSION['Admin']['id']) && empty($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Session expired. Please log in.';
    exit;
}

$voucher = isset($_GET['voucher']) ? trim((string) $_GET['voucher']) : '';
if ($voucher !== '' && $voucher !== 'product_opening') {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid voucher';
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;
$characteristic_id = isset($_GET['characteristic_id']) ? (int) $_GET['characteristic_id'] : 0;

$product_name_db = '';
if ($product_id > 0) {
    $pr = getRecord('SELECT name FROM tbl_products WHERE id = ' . $product_id . ' LIMIT 1');
    if ($pr && isset($pr['name'])) {
        $product_name_db = trim((string) $pr['name']);
    }
}

// Master lists (same as stock-journal-create): dropdown columns get Excel data validation.
$sample_category_names = [];
foreach (getList('SELECT id, name FROM tbl_categories WHERE status = 1 ORDER BY name ASC') as $c) {
    $n = trim((string) ($c['name'] ?? ''));
    if ($n === '') {
        continue;
    }
    $sample_category_names[] = $n;
}
$sample_category_names = array_values(array_unique($sample_category_names));

$sample_location_names = [];
foreach (getList('SELECT id, name FROM tbl_location WHERE status = 1 ORDER BY id ASC') as $l) {
    $n = trim((string) ($l['name'] ?? ''));
    if ($n === '') {
        continue;
    }
    $sample_location_names[] = $n;
}
$sample_location_names = array_values(array_unique($sample_location_names));

// Must match `window.CALCULATION_MODES` / `buildCalculationSelectOptions()` in stock-journal-create.php
// (row calculation dropdown) — not tbl_calculation_modes, which is used elsewhere for different labels.
$sample_calculation_names = [
    'Carat X Rate',
    'Rate X Gross Wt',
    'Rate X Purity Wt',
    'Rate X Net Wt',
    'Rate X Final Wt',
    'Fix',
    'Stone Charge',
    'Attach Image Type',
];

$sample_product_labels = [];
if ($characteristic_id > 0) {
    $pchars = getList(
        'SELECT p.name, m.display_name AS metal_name
        FROM tbl_product_characteristics pc
        INNER JOIN tbl_products p ON pc.product_id = p.id
        INNER JOIN tbl_metal m ON m.id = pc.metal_id
        WHERE pc.id = ' . (int) $characteristic_id . ' AND p.status = 1 AND pc.status = 1
        ORDER BY p.name ASC, pc.id ASC'
    );
    foreach ($pchars as $row) {
        $n = trim((string) ($row['name'] ?? ''));
        $mn = trim((string) ($row['metal_name'] ?? ''));
        if ($n === '') {
            continue;
        }
        $sample_product_labels[] = $mn !== '' ? $n . ' - ' . $mn : $n;
    }
    $sample_product_labels = array_values(array_unique($sample_product_labels));
}
if (empty($sample_product_labels) && $product_name_db !== '') {
    $sample_product_labels = [$product_name_db];
}

// Metal-section Carat = Karat (tbl_carat) — same as .carat-select in stock-journal-create.php. The first
// template column "Carat" (after Gross Wt.) is diamond carat/weight; only the second "Carat" is karat.
$sample_carat_names = [];
foreach (getList('SELECT id, name FROM tbl_carat WHERE status = 1 ORDER BY id ASC') as $cr) {
    $n = trim((string) ($cr['name'] ?? ''));
    if ($n === '') {
        continue;
    }
    $sample_carat_names[] = $n;
}
$sample_carat_names = array_values(array_unique($sample_carat_names));

$sample_discount_types = ['Fix', 'On Amount', 'On Making Amount', 'On Diamond Amount', 'On Stone Amount', 'On Net Amount', 'On Percentage'];
$sample_making_types = ['Fix', 'Per Gram', 'Per Piece', 'Per Kilogram', 'Per Percent', 'MRP', 'M.KT'];
$sample_stone_charge_types = ['Fix', 'Per Gram'];
$sample_tax_type_labels = ['Tax on making', 'Tax of net amount', 'No tax'];
$sample_other_charge_types = ['Fix', 'Percentage'];

$product_display_for_sample = $product_name_db;
if ($characteristic_id > 0) {
    $pRow = getRecord(
        'SELECT p.name, m.display_name AS metal_name
        FROM tbl_product_characteristics pc
        INNER JOIN tbl_products p ON pc.product_id = p.id
        INNER JOIN tbl_metal m ON m.id = pc.metal_id
        WHERE pc.id = ' . (int) $characteristic_id . ' AND p.status = 1 AND pc.status = 1
        LIMIT 1'
    );
    if ($pRow) {
        $n = trim((string) ($pRow['name'] ?? ''));
        $mn = trim((string) ($pRow['metal_name'] ?? ''));
        if ($n !== '') {
            $product_display_for_sample = $mn !== '' ? $n . ' - ' . $mn : $n;
        }
    }
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Stock import');

// Column order matches product selection / modal groups (same labels as Show/Hide columns UI).
$headers = [
    'Images',
    'Action',
    'Column Groups',
    'Basic Information',
    'Id',
    'RFIDCode',
    'Voucher Type',
    'Photo',
    'Barcode',
    'Design No',
    'HUID No',
    'Category',
    'Calculation',
    'Product',
    'Location',
    'Diamond group',
    'Pkt. Wt.',
    'PKt. Less Wt.',
    'Gross Wt.',
    'Carat',
    'D.Weight',
    'Net Wt.',
    'Quantity',
    'Rate',
    'Amount',
    'Metal group',
    'Metal Qty',
    'Weight',
    'Carat',
    'Purity %',
    'Purity Wt',
    'Loss Wt.',
    'Loss Wt. Per',
    'Loss Value',
    'Wastage Per',
    'Wastage Wt',
    'Metal Rate',
    'Metal Value',
    'Metal Cost',
    'Request & Final Wt.',
    'Requested Purity',
    'Requested',
    'Setting Charge',
    'Final Wt.',
    'Alloy Wt.',
    'Discount (group)',
    'Discount Type',
    'Discount Per.',
    'Discount Amount',
    'Discount',
    'Making (group)',
    'Making Type',
    'Making Rate',
    'Making Discount Amt.',
    'Making Amount',
    'Making Actual Value',
    'Making Cost',
    'Minimum',
    'Minimum Price',
    'Minimum Code',
    'Stone group',
    'Stone Charge Type',
    'Stone Rate',
    'Stone Amount',
    'Stone Cost',
    'Diamond Amount',
    'Amounts',
    'Purchase Amount',
    'Sale Amount',
    'Sale Amount With Tax',
    'Net Amt',
    'Tax Type',
    'Tax %',
    'Tax',
    'Other Charge (group)',
    'Other Charge Type',
    'Other Weight',
    'Other Rate',
    'Other Info',
    'Other Amount',
    'Hallmark',
    'Hallmark Amount',
    'Hallmark Rate',
    'Net Amt+Tax / Reverse',
    'Net Amt+Tax',
    'Reverse',
    'Product Name',
    'Product ID',
    'Characteristic ID',
];

$sheet->fromArray([$headers], null, 'A1', true);

$exampleRow = array_fill(0, count($headers), '');
// Sample values (by header label — first matching column wins for duplicates except Carat).
$labelIndices = [];
foreach ($headers as $i => $label) {
    $labelIndices[$label][] = $i;
}
$set = function (string $label, $value) use (&$exampleRow, $labelIndices) {
    if (!empty($labelIndices[$label])) {
        $exampleRow[$labelIndices[$label][0]] = $value;
    }
};
$set('Metal Qty', 1);
$set('Weight', 10.5);
$set('Purity %', 91.75);
$set('Loss Wt.', 0);
$set('Design No', 'DN001');
$set('HUID No', 'HUID001');
$set('RFIDCode', 'RFID001');
$set('Barcode', '');
$set('Images', '(optional: URL, base64, path, or embed picture)');
if ($product_display_for_sample !== '') {
    $set('Product', $product_display_for_sample);
}
// Second "Carat" column (metal / karat dropdown). First "Carat" in the sheet = diamond D. weight column in UI, not a karat select.
if (isset($labelIndices['Carat']) && count($labelIndices['Carat']) > 1) {
    $exampleRow[$labelIndices['Carat'][1]] = $sample_carat_names[0] ?? '';
}
// Last columns: fixed context for this voucher (do not change when adding rows — import uses page / form product & characteristic).
$set('Product Name', $product_name_db);
$set('Product ID', $product_id > 0 ? $product_id : '');
$set('Characteristic ID', $characteristic_id > 0 ? $characteristic_id : '');

$sheet->fromArray([$exampleRow], null, 'A2', true);

$lastCol = Coordinate::stringFromColumnIndex(count($headers));

$sheet->freezePane('A2');
$sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
$sheet->getStyle('A1:' . $lastCol . '1')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setARGB('FFE2E8F0');
$sheet->getStyle('A1:' . $lastCol . '1')->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER)
    ->setWrapText(true);

for ($c = 1; $c <= count($headers); $c++) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
}
$sheet->getRowDimension(1)->setRowHeight(28);
$sheet->getRowDimension(2)->setRowHeight(32);

// Hidden sheet holding allowed values so Excel can show the same options as the web (supports commas in labels).
$listSheetTitle = 'ListValues';
$listSheet = $spreadsheet->createSheet();
$listSheet->setTitle($listSheetTitle);

$sj_excel_list_write = static function (Worksheet $ls, int $listCol, array $vals): int {
    $i = 0;
    $colLetter = Coordinate::stringFromColumnIndex($listCol);
    foreach ($vals as $v) {
        $i++;
        $ls->setCellValue($colLetter . $i, (string) $v);
    }

    return $i;
};

$listDefOrder = [
    'category' => $sample_category_names,
    'calculation' => $sample_calculation_names,
    'product' => $sample_product_labels,
    'location' => $sample_location_names,
    'carat' => $sample_carat_names,
    'discount_type' => $sample_discount_types,
    'making_type' => $sample_making_types,
    'stone_charge_type' => $sample_stone_charge_types,
    'tax_type' => $sample_tax_type_labels,
    'other_charge_type' => $sample_other_charge_types,
];

$listRangeMeta = [];
$nextListCol = 1;
foreach ($listDefOrder as $listKey => $listVals) {
    $nRows = $sj_excel_list_write($listSheet, $nextListCol, $listVals);
    if ($nRows < 1) {
        continue;
    }
    $listRangeMeta[$listKey] = [
        'col1' => $nextListCol,
        'n' => $nRows,
        'colLetter' => Coordinate::stringFromColumnIndex($nextListCol),
    ];
    $nextListCol++;
}

$listSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

$spreadsheet->setActiveSheetIndex(0);

$dvFormula = static function (string $title, string $colLetter, int $n) {
    if ($n < 1) {
        return null;
    }

    return "'" . $title . "'!" . '$' . $colLetter . '$1:$' . $colLetter . '$' . $n;
};

$applyListDv = static function (Worksheet $sh, int $dataCol1Based, ?string $f1, int $maxDataRow): void {
    if ($f1 === null) {
        return;
    }
    $colL = Coordinate::stringFromColumnIndex($dataCol1Based);
    $val = new DataValidation();
    $val->setType(DataValidation::TYPE_LIST);
    $val->setErrorStyle(DataValidation::STYLE_STOP);
    $val->setAllowBlank(true);
    $val->setShowInputMessage(true);
    $val->setShowErrorMessage(true);
    // PhpSpreadsheet Xlsx writer inverts this flag: use true so OOXML showDropDown="0"
    // (show the in-cell list arrow; false produces showDropDown="1" and hides the arrow in Excel).
    $val->setShowDropDown(true);
    $val->setPromptTitle('Allowed values');
    $val->setPrompt('Options match the Stock Journal product opening form (same masters as the page).');
    $val->setErrorTitle('Value not in list');
    $val->setError('Choose from the list, or clear the cell.');
    $val->setFormula1($f1);
    $sh->setDataValidation($colL . '2:' . $colL . $maxDataRow, $val);
};

$headerToCol1 = static function (array $hdr, string $label) {
    $i = array_search($label, $hdr, true);

    return $i === false ? null : ((int) $i + 1);
};

$getFormula1ForListKey = static function (string $key) use ($listSheetTitle, $listRangeMeta, $dvFormula) {
    if (empty($listRangeMeta[$key]) || (int) ($listRangeMeta[$key]['n'] ?? 0) < 1) {
        return null;
    }
    $m = $listRangeMeta[$key];

    return $dvFormula($listSheetTitle, (string) $m['colLetter'], (int) $m['n']);
};

$maxDvRows = 5000;
$labelToListKey = [
    'Category' => 'category',
    'Calculation' => 'calculation',
    'Product' => 'product',
    'Location' => 'location',
    'Discount Type' => 'discount_type',
    'Making Type' => 'making_type',
    'Stone Charge Type' => 'stone_charge_type',
    'Tax Type' => 'tax_type',
    'Other Charge Type' => 'other_charge_type',
];
foreach ($labelToListKey as $headerLabel => $mkey) {
    $c1 = $headerToCol1($headers, $headerLabel);
    if ($c1 === null) {
        continue;
    }
    $f1 = $getFormula1ForListKey($mkey);
    if ($f1 === null) {
        continue;
    }
    $applyListDv($sheet, $c1, $f1, $maxDvRows);
}
// Only the second "Carat" column = metal karat dropdown; first "Carat" in the sheet = diamond D. weight (not this list).
if (isset($labelIndices['Carat'], $listRangeMeta['carat']) && count($labelIndices['Carat']) > 1) {
    $cCar2 = (int) $labelIndices['Carat'][1] + 1;
    $f1k = $getFormula1ForListKey('carat');
    if ($f1k !== null) {
        $applyListDv($sheet, $cCar2, $f1k, $maxDvRows);
    }
}

$filename = 'stock_journal_product_opening_sample';
if ($product_id > 0) {
    $filename .= '_product' . $product_id;
}
$filename .= '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
