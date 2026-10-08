<?php
/**
 * Parts Order Import: paste a supplier invoice / order confirmation, have Claude
 * read it, and get back line items matched to tracker parts with shipping, tax
 * and fees prorated across lines. Read-only: it never writes to the database.
 * The Parts tab review UI records the confirmed lines through api.php's
 * existing checkin_inventory action.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;

header('Content-Type: application/json');
requireLogin();
set_time_limit(240);

$action = $_POST['action'] ?? '';
if ($action !== 'parse') {
    jsonResponse(['error' => 'Unknown action'], 400);
}

$text = trim($_POST['text'] ?? '');
if ($text === '') {
    jsonResponse(['error' => 'Paste an order or invoice first.'], 400);
}
if (strlen($text) > 100000) {
    jsonResponse(['error' => 'That paste is too long (over 100,000 characters). Paste just the order details.'], 400);
}

// Keep in sync with CATEGORY_PREFIXES in index.php (the New Part form's categories)
const PART_CATEGORIES = ['3D Printed', 'Connector', 'Hardware', 'Mechanical', 'Electronics', 'PCB', 'Packaging', 'Tool', 'Other'];

$env = parse_ini_file(__DIR__ . '/.env');
$apiKey = $env['ANTHROPIC_API_KEY'] ?? '';
if ($apiKey === '') {
    jsonResponse(['error' => 'No Claude API key is set yet. Use the "Claude API key" link next to the Read Order button to add one.'], 500);
}

$db = getDB();

// Parts catalog for matching, including each part's known supplier part numbers
$parts = $db->query("
    SELECT p.id, p.part_number, p.part_name, p.description, p.category,
           GROUP_CONCAT(DISTINCT NULLIF(ps.supplier_part_number, '') SEPARATOR ', ') AS supplier_part_numbers,
           GROUP_CONCAT(DISTINCT NULLIF(ps.supplier_name, '') SEPARATOR ', ') AS supplier_names
    FROM parts p
    LEFT JOIN part_sources ps ON ps.part_id = p.id
    GROUP BY p.id
    ORDER BY p.part_number
")->fetchAll();

// Each part's most recent order note: past invoices often name the supplier's
// own item reference (e.g. a JLC "3D Printing Module" order id), which helps matching.
$recentNotes = [];
foreach ($db->query("
    SELECT ic.part_id, ic.supplier_name, ic.notes
    FROM inventory_checkins ic
    JOIN (SELECT part_id, MAX(id) AS id FROM inventory_checkins WHERE notes IS NOT NULL AND notes <> '' GROUP BY part_id) last
      ON last.id = ic.id
") as $r) {
    $recentNotes[$r['part_id']] = trim(($r['supplier_name'] ? $r['supplier_name'] . ': ' : '') . mb_substr($r['notes'], 0, 200));
}

$supplierNames = $db->query("
    SELECT supplier_name FROM inventory_checkins WHERE supplier_name <> '' GROUP BY supplier_name ORDER BY COUNT(*) DESC
")->fetchAll(PDO::FETCH_COLUMN);

$catalogLines = [];
$partsById = [];
foreach ($parts as $p) {
    $partsById[(int) $p['id']] = $p;
    $line = "id={$p['id']} | {$p['part_number']} | {$p['part_name']}";
    if ($p['description'])           $line .= ' | ' . preg_replace('/\s+/', ' ', $p['description']);
    if ($p['category'])              $line .= " | category: {$p['category']}";
    if ($p['supplier_part_numbers']) $line .= " | supplier part #: {$p['supplier_part_numbers']}";
    if ($p['supplier_names'])        $line .= " | sources: {$p['supplier_names']}";
    if (isset($recentNotes[$p['id']])) $line .= " | last order note: {$recentNotes[$p['id']]}";
    $catalogLines[] = $line;
}

$system = <<<TXT
You read supplier invoices, order confirmations and receipts that the owner of a small ham radio kit business pastes in, and extract the parts order so it can be logged in the business's inventory tracker.

The paste is usually copied straight off a web page or email, so expect navigation menus, addresses, marketing text and other noise around the order itself. Extract only what was actually purchased.

List every item purchased, including things that aren't inventory parts (tools, shop supplies, personal items); give those matched_part_id null. The owner skips them, but they must still be listed so shipping and tax are split correctly.

Shipping, delivery, handling, tax, duties, customs and fees are never line items, even when the page lists them in the same table as products. Report them only in the order-level fields below.

For each purchased line item:
- units: the total number of individual pieces received. If the item is sold in packs, multiply (e.g. "13 packs of 50" is 650 units).
- line_total: the merchandise amount for that line before shipping and tax.
- matched_part_id: the id of the tracker part this line is, from the catalog below, or null if none fits. Match on supplier part numbers first, then on name, description and the last order note. Only match when you're confident; a wrong match puts stock on the wrong part, while null just asks the owner to pick. Two different lines should not match the same part unless the invoice really does list the same item twice.
- match_note: a few words on why it matched (or why nothing fit).
- suggested_name and suggested_category: only when matched_part_id is null and the item looks like something the business would stock (a component, hardware, packaging, a printed part), suggest a short tracker-style part name in the catalog's naming style (e.g. "M2x3mm Set Screw - Brass", "3x4 Zip Bag") and the closest category, spelled exactly as one of: %CATEGORIES%. Use null for both when the line matched, or for things the business wouldn't keep as inventory (personal items, equipment or supplies for its own use).

For the order as a whole, report merchandise_total, shipping, tax (include import duties and customs), other_fees (anything else charged, minus any discounts), and grand_total, exactly as printed. Shipping is what was actually paid for shipping: if a shipping charge is shown and then cancelled out by a free-shipping discount or promotion, report 0. Use 0 for a charge the invoice shows as zero or doesn't list, and null for grand_total only if no total is printed.

order_date: the date the order was placed, as YYYY-MM-DD, or null if none is shown. Invoices from Asian suppliers (JLCPCB, PCBWay, LCSC, AliExpress) often write dates day-first, e.g. 08/10/2026 is 8 October 2026.

supplier_name: if the supplier is one the tracker already uses, spell it exactly as the tracker does. Existing supplier names: %SUPPLIERS%

order_number: the supplier's order, invoice or confirmation number that the owner would use to look the order up.

Don't warn about possible duplicate orders; the tracker checks its own records for that. Write match notes and warnings as short plain sentences. Don't use em dashes.

Tracker parts catalog:
%CATALOG%
TXT;
$system = str_replace(
    ['%SUPPLIERS%', '%CATALOG%', '%CATEGORIES%'],
    [implode(', ', $supplierNames) ?: '(none yet)', implode("\n", $catalogLines), implode(', ', PART_CATEGORIES)],
    $system
);

$schema = [
    'type' => 'object',
    'properties' => [
        'supplier_name'    => ['type' => 'string'],
        'order_number'     => ['type' => ['string', 'null']],
        'order_date'       => ['type' => ['string', 'null']],
        'currency'         => ['type' => 'string'],
        'merchandise_total'=> ['type' => 'number'],
        'shipping'         => ['type' => 'number'],
        'tax'              => ['type' => 'number'],
        'other_fees'       => ['type' => 'number'],
        'grand_total'      => ['type' => ['number', 'null']],
        'lines' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'description'          => ['type' => 'string'],
                    'supplier_part_number' => ['type' => ['string', 'null']],
                    'quantity_text'        => ['type' => 'string', 'description' => 'Quantity exactly as written on the invoice, e.g. "13 packs of 50"'],
                    'units'                => ['type' => 'integer'],
                    'line_total'           => ['type' => 'number'],
                    'matched_part_id'      => ['type' => ['integer', 'null']],
                    'match_note'           => ['type' => 'string'],
                    'suggested_name'       => ['type' => ['string', 'null']],
                    'suggested_category'   => ['type' => ['string', 'null']],
                ],
                'required' => ['description', 'supplier_part_number', 'quantity_text', 'units', 'line_total', 'matched_part_id', 'match_note', 'suggested_name', 'suggested_category'],
                'additionalProperties' => false,
            ],
        ],
        'warnings' => [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'description' => 'Anything the owner should double-check, e.g. a total that does not add up or a quantity that was ambiguous. Empty if nothing.',
        ],
    ],
    'required' => ['supplier_name', 'order_number', 'order_date', 'currency', 'merchandise_total', 'shipping', 'tax', 'other_fees', 'grand_total', 'lines', 'warnings'],
    'additionalProperties' => false,
];

$client = new Client(apiKey: $apiKey);

try {
    $message = $client->beta->messages->create(
        model: 'claude-opus-5',
        maxTokens: 16000,
        betas: ['server-side-fallback-2026-07-01'],
        fallbacks: 'default',
        thinking: ['type' => 'adaptive'],
        system: $system,
        messages: [['role' => 'user', 'content' => "Here's the order:\n\n" . $text]],
        outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
    );
} catch (AuthenticationException $e) {
    jsonResponse(['error' => 'Anthropic rejected the saved API key. It may have been deleted. Use the "Claude API key" link to replace it.'], 502);
} catch (RateLimitException $e) {
    jsonResponse(['error' => 'Claude is rate limiting requests right now. Wait a minute and try again.'], 503);
} catch (APIStatusException $e) {
    jsonResponse(['error' => 'Claude API error: ' . $e->getMessage()], 502);
} catch (APIConnectionException $e) {
    jsonResponse(['error' => 'Could not reach the Claude API: ' . $e->getMessage()], 502);
}

if ($message->stopReason === 'refusal') {
    jsonResponse(['error' => 'Claude declined to read this paste. Try pasting just the order lines and totals.'], 422);
}
if ($message->stopReason === 'max_tokens') {
    jsonResponse(['error' => 'The order was too long to read in one pass. Try pasting it in smaller pieces.'], 422);
}

// Read the last text block: if a fallback model took over mid-answer, earlier
// text blocks are the declined model's partial output.
$data = null;
foreach ($message->content as $block) {
    if ($block->type === 'text') {
        $data = json_decode($block->text, true);
    }
}
if (!is_array($data)) {
    jsonResponse(['error' => 'Could not understand Claude\'s reply. Try again.'], 502);
}

// ── Prorate shipping / tax / fees across lines by merchandise share ──
// Done here, not by the model, so the cents are consistent.
// Backstop for the instruction above: an unmatched line that is really a charge
// would otherwise skip proration and make every other line look cheaper.
// Also drops empty unmatched rows (no units, no cost) the model occasionally emits.
$lines = array_values(array_filter($data['lines'], fn($l) =>
    $l['matched_part_id'] !== null
    || ((int) $l['units'] > 0 || (float) $l['line_total'] != 0)
    && !preg_match('/\b(shipping|freight|delivery|handling|postage|tax|taxes|vat|duty|duties|customs|fee|fees)\b/i', $l['description'])
));
$lineSum = array_sum(array_map(fn($l) => (float) $l['line_total'], $lines));
// Extras come only from the printed charges, and each line's share is taken
// against the printed merchandise total. Never derive extras as "grand total
// minus lines": a missing line (e.g. a non-inventory item the model left out)
// would then dump its whole cost onto the lines that are listed.
$merch = (float) $data['merchandise_total'];
$extras = round((float) $data['shipping'] + (float) $data['tax'] + (float) $data['other_fees'], 2);
$base = $merch > 0 ? $merch : $lineSum;
$linesComplete = abs($lineSum - $merch) <= 0.02;

$warnings = $data['warnings'];
if (!$linesComplete) {
    $warnings[] = sprintf('Line items add up to $%.2f but the invoice says merchandise is $%.2f. A line may be missing; the listed lines still only get their own share of shipping and tax.', $lineSum, $merch);
}
if ($data['grand_total'] !== null && abs($merch + $extras - (float) $data['grand_total']) > 0.02) {
    $warnings[] = sprintf('Merchandise plus shipping, tax and fees comes to $%.2f, but the printed total is $%.2f. Check the shipping and tax figures above.', $merch + $extras, $data['grand_total']);
}

$allocated = 0.0;
$lastIdx = count($lines) - 1;
foreach ($lines as $i => &$l) {
    // Last line takes the rounding remainder only when every line is present
    $share = $base > 0
        ? ($linesComplete && $i === $lastIdx ? round($extras - $allocated, 2) : round($extras * (float) $l['line_total'] / $base, 2))
        : 0.0;
    $allocated += $share;
    $l['extra_share'] = $share;
    $l['gross_total'] = round((float) $l['line_total'] + $share, 2);

    $pid = $l['matched_part_id'];
    if ($pid !== null && !isset($partsById[$pid])) {
        $l['match_note'] = "Suggested part id $pid doesn't exist; pick one.";
        $l['matched_part_id'] = null;
    }
    $l['match_note'] = str_replace(' — ', ', ', $l['match_note']);
    // Category is free text in the schema; only pass through ones the New Part form knows
    if (!in_array($l['suggested_category'] ?? null, PART_CATEGORIES, true)) $l['suggested_category'] = null;
}
unset($l);

// Already logged? Same part, same quantity and same total within the last 60 days
// almost always means this invoice was entered before.
$dupeStmt = $db->prepare("
    SELECT purchase_date FROM inventory_checkins
    WHERE part_id = ? AND quantity = ? AND ABS(total_cost - ?) < 0.02
      AND purchase_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
    ORDER BY purchase_date DESC LIMIT 1
");
foreach ($lines as $l) {
    if ($l['matched_part_id'] === null) continue;
    $dupeStmt->execute([$l['matched_part_id'], $l['units'], $l['gross_total']]);
    if ($date = $dupeStmt->fetchColumn()) {
        $warnings[] = sprintf('%s already has an order for %d units at $%.2f dated %s. This may be a duplicate.',
            $partsById[$l['matched_part_id']]['part_number'], $l['units'], $l['gross_total'], $date);
    }
}
$warnings = array_map(fn($w) => str_replace(' — ', ', ', $w), $warnings);

$data['lines'] = $lines;
$data['extras_total'] = round($extras, 2);
$data['warnings'] = $warnings;

jsonResponse(['success' => true, 'order' => $data]);
