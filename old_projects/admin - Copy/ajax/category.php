<?php
session_start();
require_once "../config.php";
header('Content-Type: application/json');

$user   = $_SESSION['Admin']['id'] ?? 0;
$action = $_POST['action'] ?? '';

if($action === "add"){
    $name = esc($_POST['name'] ?? '');
    $short_code = esc($_POST['short_code'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $min_qty = isset($_POST['min_qty']) ? (float)$_POST['min_qty'] : 0;
    $max_qty = isset($_POST['max_qty']) ? (float)$_POST['max_qty'] : 0;
    $min_wt = isset($_POST['min_wt']) ? (float)$_POST['min_wt'] : 0;
    $max_wt = isset($_POST['max_wt']) ? (float)$_POST['max_wt'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 1;

    if(empty($name)){
        echo json_encode([
            "status" => "error",
            "message" => "Category name is required"
        ]);
        exit;
    }

    $sql = "INSERT INTO tbl_categories (name, short_code, parent_id, min_qty, max_qty, min_wt, max_wt, status, created_by, created_at) 
            VALUES ('$name', '$short_code', $parent_id, $min_qty, $max_qty, $min_wt, $max_wt, $is_active, $user, NOW())";

    if(mysqli_query($conn, $sql)){
        $new_id = mysqli_insert_id($conn);
        echo json_encode([
            "status" => "success",
            "message" => "Category added successfully",
            "id" => $new_id,
            "name" => $name
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . mysqli_error($conn)
        ]);
    }
    exit;
}

if($action === "update"){
    $id   = intval($_POST['id'] ?? 0);
    $name = esc($_POST['name'] ?? '');
    $short_code = esc($_POST['short_code'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $min_qty = isset($_POST['min_qty']) ? (float)$_POST['min_qty'] : 0;
    $max_qty = isset($_POST['max_qty']) ? (float)$_POST['max_qty'] : 0;
    $min_wt = isset($_POST['min_wt']) ? (float)$_POST['min_wt'] : 0;
    $max_wt = isset($_POST['max_wt']) ? (float)$_POST['max_wt'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if(empty($name) || $id <= 0){
        echo json_encode([
            "status" => "error",
            "message" => "Invalid data"
        ]);
        exit;
    }

    $sql = "UPDATE tbl_categories 
            SET name = '$name', short_code = '$short_code', parent_id = $parent_id, 
                min_qty = $min_qty, max_qty = $max_qty, min_wt = $min_wt, max_wt = $max_wt, 
                status = $is_active, updated_at = NOW()
            WHERE id = $id";

    if(mysqli_query($conn, $sql)){
        echo json_encode([
            "status" => "success",
            "message" => "Category updated successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . mysqli_error($conn)
        ]);
    }
    exit;
}

// Get all categories
if($action === "list" || empty($action)){
    $categories = getList("SELECT id, name, short_code FROM tbl_categories WHERE status = 1 ORDER BY name ASC");
    echo json_encode([
        "status" => "success",
        "categories" => $categories
    ]);
    exit;
}

echo json_encode([
    "status" => "error",
    "message" => "Invalid action"
]);
?>

