<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use Illuminate\Contracts\Console\Kernel;

$companyId = 1;

// Raw rows from OLD_DATA
$tsv = <<<'TSV'
5	Nandu ROad Carrier	46000	42000	106	HR69D9329
6	All india ROadlines	44000	41000	107	MH12WJ3637
7	Shree SHyam Trans Container	72000	67000	113	MH23AU7157
8	All india ROadlines	16500	15500	115	MH48DC2512
9	EATA kanpur transport	74000	69000	112	UP75M9952
10	INDIA KING ROADWAYS	95000	90000	110 111	UP80ET6823
11	SAKSHI CONTAINER SERVICE	64000	60000	114	KA52B2062
12	ANDHRA CAPITAL	38000	35000	116	DD01Q9756
13	BABA RAMDEV FREIGHT CARRIER	62000	57000	117	DD01AE9540
14	EATA kanpur transport	107000	102000	118	UP75M5517
16	POONAM ROADLINES	11000	10000	120	MH04JK0253
17	RAVI TRANSPORT	85000	80000	121	KA44A2339
18	JEEN MATA TRANPSORT	54000	50000	122 123	DD01N9770
19	JEEN MATA TRANPSORT	71000	66000	124	MH44U4815
20	SHREE NATH RAODLINES	14400	13400	125	MH04DS1424
21	JAI HANUMAN TRANSPORT COROPORATION	65000	60000	108	HR55BB4477
22	JAI HANUMAN TRANSPORT COROPORATION	65000	60000	109	HR55BC0377
23	NEW KISHAN ROADLINES	9000	8000	101	GJ15AT9874
35	NEW SIDDIQUE TRANSPORT	25000	24000	152	GJ15AX0474
39	EATA kanpur transport	133000	125000	141 142	UP82T7653
40	CHITRANSH CARGO	54000	50000	143 144	MH48DQ4436
41	CHAITNYA CARGO MOVERS	64000	60000	145	DN09V9933
42	KIRUBA ROADWAYS	102000	97000	153 154 202 203	TN88U2965
44	VIJAY ROADLINES	47500	44500	149	KA04AF1959
45	SHREE GNESH ROALINES	27000	26000	150	GJ03CU3382
46	POONAM ROADLINES	7000	6500	301	GJ15AU4976
75	RUPINDER KAUR GILL	150000	140000	335	CG04QT9695
76	CHOUDHARY TRANSPORT CO	35000	33000	337	RJ46GA5813
77	INDIAN ROAD CARRIER	30000	28000	336	WB23C7936
78	BERWAL CONTAINER SERVICE	47500	45500	338	DD01W9455
79	BABA RAMDEV FREIGHT CARRIER	68000	64000	339	NL01AB2872
80	SOUTH GOLDEN CARRIER	28000	27000	340	KL05BF5628
81	MAA SAMALESHWARI ROADLINES	100000	95000	341	MH48DC6209
82	NEW SIDDIQUE ROADLINES	45000	43000	342 343	MH24AB9592
TSV;

$lines = explode("\n", trim($tsv));
$parsed = [];
foreach ($lines as $line) {
    $parts = explode("\t", trim($line));
    if (count($parts) >= 6) {
        $parsed[] = [
            'challan_no' => trim($parts[0]),
            'paid_to' => trim($parts[1]),
            'hire' => (int) trim($parts[2]),
            'advance' => (int) trim($parts[3]),
            'lr_no' => trim($parts[4]),
            'lorry_no' => trim($parts[5]),
        ];
    }
}

echo "=== DIAGNOSIS: LORRY RECEIPTS & OLD_DATA ===\n";
echo 'Parsed '.count($parsed)." records from OLD_DATA.\n\n";

$existingChallans = [];
$missingChallans = [];

foreach ($parsed as $row) {
    $cNo = $row['challan_no'];
    $padded = str_pad($cNo, 3, '0', STR_PAD_LEFT);

    $inv = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'lorry_receipt')
        ->where(function ($q) use ($cNo, $padded) {
            $q->where('invoice_number', $cNo)
                ->orWhere('invoice_number', $padded);
        })
        ->first();

    if ($inv) {
        $existingChallans[] = [
            'row' => $row,
            'invoice' => $inv,
        ];
    } else {
        $missingChallans[] = $row;
    }
}

echo '1. EXISTING LORRY RECEIPTS IN DB: '.count($existingChallans)."\n";
foreach ($existingChallans as $item) {
    $inv = $item['invoice'];
    $r = $item['row'];
    echo "   -> Challan #{$r['challan_no']} (Inv ID: {$inv->id}, InvNo: '{$inv->invoice_number}') | Current LorryNo: '{$inv->tr_lorry_no}' | New LorryNo: '{$r['lorry_no']}' | From: '{$inv->tr_from_name}', To: '{$inv->tr_to_name}'\n";
}

echo "\n2. MISSING LORRY RECEIPTS (TO CREATE): ".count($missingChallans)."\n";
foreach ($missingChallans as $r) {
    echo "   -> Challan #{$r['challan_no']} | Paid To: '{$r['paid_to']}' | Hire: {$r['hire']} | Adv: {$r['advance']} | LR: '{$r['lr_no']}' | Lorry: '{$r['lorry_no']}'\n";
}

echo "\n3. CHECKING LR NUMBERS IN INVOICE RECEIPTS FOR FROM / TO...\n";

// Gather all LR numbers
$allLrNumbers = [];
foreach ($parsed as $r) {
    $lrs = preg_split('/\s+/', trim($r['lr_no']), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($lrs as $lr) {
        $allLrNumbers[$lr] = true;
    }
}

echo 'Total unique LR Numbers to look up: '.count($allLrNumbers)."\n";

foreach (array_keys($allLrNumbers) as $lr) {
    // Search in invoice_items for invoice_receipt
    $items = InvoiceItem::query()
        ->whereHas('invoice', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->where('template_name', 'invoice_receipt');
        })
        ->where(function ($q) use ($lr) {
            $q->where('tr_consignment_number', $lr)
                ->orWhere('name', $lr);
        })
        ->with('invoice')
        ->get();

    if ($items->count() > 0) {
        echo "   [+] LR #{$lr} found in {$items->count()} Invoice Receipt item(s):\n";
        foreach ($items as $it) {
            $inv = $it->invoice;
            $amt = $it->total ?? $it->price ?? 0;
            $amtRs = $amt / 100;
            $from = $it->tr_from_name ?: $inv->tr_from_name ?: '(empty)';
            $to = $it->tr_to_name ?: $inv->tr_to_name ?: '(empty)';
            echo "       -> Item ID {$it->id} (Inv #{$inv->invoice_number}, Inv ID {$inv->id}): From='{$from}', To='{$to}', Amount=Rs.{$amtRs} (Paise: {$amt})\n";
        }
    } else {
        // Also check if any LR Receipt exists
        $lrRec = Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'lr_receipt')
            ->where('invoice_number', $lr)
            ->first();

        if ($lrRec) {
            echo "   [*] LR #{$lr} found in LR Receipt ID {$lrRec->id}: From='{$lrRec->tr_from_name}', To='{$lrRec->tr_to_name}', Amount=Rs.".($lrRec->total / 100)."\n";
        } else {
            echo "   [-] LR #{$lr} NOT found in Invoice Receipts or LR Receipts.\n";
        }
    }
}

echo "\n=== END DIAGNOSIS ===\n";
