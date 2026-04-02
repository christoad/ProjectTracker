<?php
/**
 * Get Active Projects - Returns active projects for order form
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $db = getDB();
    
    $stmt = $db->query("
        SELECT id, project_name, retail_price, description 
        FROM projects 
        WHERE status = 'active' 
        ORDER BY project_name ASC
    ");
    
    $projects = $stmt->fetchAll();
    
    echo json_encode($projects);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load projects']);
}
?>
