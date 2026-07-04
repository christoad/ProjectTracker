<?php
// Password-protected migration: adds sort_order to project_parts
if (($_GET['pw'] ?? '') !== 'sota') { die('Wrong password. Add ?pw=sota to the URL.'); }

require_once __DIR__ . '/config.php';
$db = getDB();

echo "<pre style='font-family:monospace;padding:1rem;'>\n";
echo "=== BOM sort_order migration ===\n\n";

// 1. Add column if missing
$cols = array_column($db->query("SHOW COLUMNS FROM project_parts")->fetchAll(), 'Field');
if (!in_array('sort_order', $cols)) {
    $db->exec("ALTER TABLE project_parts ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER variation_value");
    echo "OK  Added sort_order column to project_parts\n";
} else {
    echo "SKP sort_order column already exists\n";
}

// 2. Initialise sort_order for existing rows (ordered by id within each project)
$rows = $db->query("SELECT id, project_id FROM project_parts ORDER BY project_id ASC, id ASC")->fetchAll();
$byProject = [];
foreach ($rows as $row) {
    $byProject[$row['project_id']][] = $row['id'];
}
$stmt = $db->prepare("UPDATE project_parts SET sort_order = ? WHERE id = ?");
$updated = 0;
foreach ($byProject as $pid => $ids) {
    foreach ($ids as $i => $id) {
        $stmt->execute([$i + 1, $id]);
        $updated++;
    }
}
echo "OK  Initialised sort_order for $updated rows\n";

echo "\nDone. Delete this file from the server after running.\n</pre>";
