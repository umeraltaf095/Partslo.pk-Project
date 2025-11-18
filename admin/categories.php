<?php
session_start();
require_once "../includes/db_connect.php";

// Protect admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
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
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}
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
// ---------- OPEN ADD CATEGORY MODAL ----------
function openAddModal() {
    document.getElementById("addModal").style.display = "flex";
}
// ---------- OPEN EDIT CATEGORY MODAL ----------
function openEditModal(id, name) {
    document.getElementById("edit_id").value = id;
    document.getElementById("edit_name").value = name;
    document.getElementById("editModal").style.display = "flex";
}
// ---------- CLOSE ANY MODAL ----------
function closeModal(id) {
    document.getElementById(id).style.display = "none";
}
// ---------- DELETE CATEGORY ----------
function deleteCategory(id) {
    if (confirm("Delete this category?")) {
        window.location.href = "category_delete.php?id=" + id;
    }
}
</script>

</head>
<body>

<h1 style="text-align:center;">Manage Categories</h1>

<div class="table-box">

    <!-- ADD MODAL TRIGGER -->
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
                <button class="button"
                    onclick="openEditModal('<?= $cat['id'] ?>','<?= htmlspecialchars($cat['category_name']) ?>')">
                    Edit
                </button>

                <button class="button"
                    onclick="deleteCategory(<?= $cat['id'] ?>)">
                    Delete
                </button>
            </td>
        </tr>
        <?php endforeach; ?>

    </table>
</div>



<!-- --------------------------- ADD MODAL ----------------------------- -->
<div class="modal-bg" id="addModal">
    <div class="modal-box">
        <h3>Add Category</h3>
        <form method="POST" action="category_add.php">
            <input type="text" name="category_name" placeholder="Category Name" required>
            <br><br>
            <button class="button" type="submit">Add</button>
            <button class="button" type="button" onclick="closeModal('addModal')">Close</button>
        </form>
    </div>
</div>



<!-- --------------------------- EDIT MODAL ----------------------------- -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <h3>Edit Category</h3>
        <form method="POST" action="category_edit.php">
            <input type="hidden" id="edit_id" name="id">
            <input type="text" id="edit_name" name="category_name" required>
            <br><br>
            <button class="button" type="submit">Update</button>
            <button class="button" type="button" onclick="closeModal('editModal')">Close</button>
        </form>
    </div>
</div>



</body>
</html>
