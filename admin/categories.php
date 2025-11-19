<?php
session_start();
require_once "../includes/db_connect.php";

// Protect admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ----------------- ADD CATEGORY -----------------
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);

    if ($name !== "") {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
    }

    header("Location: categories.php");
    exit;
}

// ----------------- EDIT CATEGORY -----------------
if (isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $name = trim($_POST['category_name']);

    if ($name !== "") {
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    }

    header("Location: categories.php");
    exit;
}

// ----------------- DELETE CATEGORY -----------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: categories.php");
    exit;
}

// --- FETCH ALL CATEGORIES ---
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Categories</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.table-box {
    width: 90%;
    margin: 30px auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.15);
}
table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* IMPORTANT: forces equal widths */
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}

th:nth-child(1), td:nth-child(1) { width: 10%; }   /* ID */
th:nth-child(2), td:nth-child(2) { width: 60%; }   /* Category Name */
th:nth-child(3), td:nth-child(3) { width: 30%; }   /* Actions */
.button {
    padding: 8px 12px;
    background: #111;
    color: #fff !important;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
}
.modal-bg {
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.6);
    display:none;
    justify-content:center;
    align-items:center;
}
.modal-box {
    width: 400px;
    background:white;
    padding:20px;
    border-radius:8px;
}
input {
    width:100%;
    padding:10px;
    margin-top:10px;
    border:1px solid #ccc;
    border-radius:6px;
}
</style>

<script>
function openAddModal() {
    document.getElementById("addModal").style.display = "flex";
}

function openEditModal(id, name) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_name").value = name;
    document.getElementById("editModal").style.display = "flex";
}

function closeModal(id) {
    document.getElementById(id).style.display = "none";
}

function deleteCategory(id) {
    if (confirm("Delete this category?")) {
        window.location.href = "categories.php?delete=" + id;
    }
}
</script>

</head>
<body>

<h1 style="text-align:center;">Manage Categories</h1>

<div class="table-box">
    <button class="button" onclick="openAddModal()">+ Add Category</button>

   <table>
    <tr>
        <th>ID</th>
        <th>Category Name</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($categories as $cat): ?>
    <tr>
        <td><?= $cat['id'] ?></td>
        <td><?= htmlspecialchars($cat['name']) ?></td>
        <td>
            <button class="button" onclick="openEditModal('<?= $cat['id'] ?>','<?= htmlspecialchars($cat['name']) ?>')">
                Edit
            </button>

            <button class="button" onclick="deleteCategory(<?= $cat['id'] ?>)">
                Delete
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</div>

<!-- ADD CATEGORY MODAL -->
<div class="modal-bg" id="addModal">
    <div class="modal-box">
        <h3>Add Category</h3>
        <form method="POST">
            <input type="text" name="category_name" placeholder="Category Name" required>

            <br><br>
            <button class="button" type="submit" name="add_category">Add</button>
            <button class="button" type="button" onclick="closeModal('addModal')">Close</button>
        </form>
    </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <h3>Edit Category</h3>
        <form method="POST">
            <input type="hidden" id="edit_id" name="id">
            <input type="text" id="edit_name" name="category_name" required>

            <br><br>
            <button class="button" type="submit" name="edit_category">Update</button>
            <button class="button" type="button" onclick="closeModal('editModal')">Close</button>
        </form>
    </div>
</div>

</body>
</html>
