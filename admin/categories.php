<?php
session_start();
require_once "../includes/db_connect.php";

// Protect admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Helper: generate slug from name
function makeSlug($str) {
    return strtolower(trim(preg_replace('/\s+/', '-', $str)));
}

// ----------------- ADD CATEGORY -----------------
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $slug = makeSlug($name);

    if ($name !== "") {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=name");
        $stmt->execute([$name, $slug]);
        $catId = $pdo->lastInsertId();

        // If category already existed, fetch its id
        if ($catId == 0) {
            $row = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $row->execute([$name]);
            $catId = $row->fetchColumn();
        }

        // Insert subcategories if any
        if (!empty($_POST['subcategories'])) {
            $subStmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=name");
            foreach ($_POST['subcategories'] as $subName) {
                $subName = trim($subName);
                if ($subName !== "") {
                    $subSlug = makeSlug($subName);
                    $subStmt->execute([$catId, $subName, $subSlug]);
                }
            }
        }
    }

    header("Location: categories.php");
    exit;
}

// ----------------- EDIT CATEGORY -----------------
if (isset($_POST['edit_category'])) {
    $id   = (int)$_POST['id'];
    $name = trim($_POST['category_name']);
    $slug = makeSlug($name);

    if ($name !== "") {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $id]);
    }

    // ── Update / keep existing sub-categories ──
    $submittedIds   = array_map('intval', $_POST['sub_ids']   ?? []);
    $submittedNames = $_POST['sub_names'] ?? [];

    if (!empty($submittedIds)) {
        $updateSub = $pdo->prepare("UPDATE subcategories SET name = ?, slug = ? WHERE id = ? AND category_id = ?");
        foreach ($submittedIds as $i => $subId) {
            $subName = trim($submittedNames[$i] ?? '');
            if ($subName !== '') {
                $updateSub->execute([$subName, makeSlug($subName), $subId, $id]);
            }
        }
    }

    // ── Delete sub-categories that were removed in the modal ──
    $existing = $pdo->prepare("SELECT id FROM subcategories WHERE category_id = ?");
    $existing->execute([$id]);
    $allSubIds = $existing->fetchAll(PDO::FETCH_COLUMN);
    $toDelete  = array_diff($allSubIds, $submittedIds);
    if (!empty($toDelete)) {
        $ph = implode(',', array_fill(0, count($toDelete), '?'));
        $pdo->prepare("DELETE FROM subcategories WHERE id IN ($ph)")->execute(array_values($toDelete));
    }

    // ── Insert newly added sub-categories ──
    if (!empty($_POST['new_subcategories'])) {
        $addSub = $pdo->prepare("INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?)");
        foreach ($_POST['new_subcategories'] as $subName) {
            $subName = trim($subName);
            if ($subName !== '') {
                $addSub->execute([$id, $subName, makeSlug($subName)]);
            }
        }
    }

    header("Location: categories.php");
    exit;
}

// ----------------- DELETE CATEGORY -----------------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // subcategories will cascade-delete if FK is set; otherwise delete manually
    $pdo->prepare("DELETE FROM subcategories WHERE category_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);

    header("Location: categories.php");
    exit;
}

// --- FETCH ALL CATEGORIES WITH SUBCATEGORY NAMES ---
$stmt = $pdo->query("SELECT c.*, GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS subcats
                     FROM categories c
                     LEFT JOIN subcategories s ON s.category_id = c.id
                     GROUP BY c.id
                     ORDER BY c.id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- FETCH SUBCATEGORIES GROUPED BY CATEGORY (for Edit modal) ---
$subRows = $pdo->query("SELECT id, category_id, name FROM subcategories ORDER BY category_id, name")->fetchAll(PDO::FETCH_ASSOC);
$subsByCategory = [];
foreach ($subRows as $sub) {
    $subsByCategory[$sub['category_id']][] = ['id' => $sub['id'], 'name' => $sub['name']];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Categories – PartsLo.pk</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #4b134f;
        --secondary: #c94b4b;
        --bg-color: #f8fafc;
        --text-dark: #334155;
        --text-muted: #64748b;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-dark);
    }

    /* Navbar */
    .navbar {
        background: #1e293b;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .navbar .brand {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        text-decoration: none;
        letter-spacing: 1px;
    }

    .navbar .nav-links a {
        padding: 8px 16px;
        background: rgba(255,255,255,0.1);
        border-radius: 6px;
        color: white;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: background 0.3s;
        margin-left: 10px;
    }

    .navbar .nav-links a:hover {
        background: rgba(255,255,255,0.2);
    }

    /* container */
    .container { max-width:1100px; margin:40px auto; padding: 0 20px; }

    .page-header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
    .page-header h1 { font-size: 30px; margin: 0; color: #0f172a; }

    /* card */
    .table-box {
        width:100%;
        background: var(--card-bg);
        padding:25px;
        border-radius:12px;
        box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
        box-sizing: border-box;
    }

    /* table */
    .table-responsive { overflow-x: auto; width: 100%; }
    table { width:100%; border-collapse:collapse; min-width: 600px; }
    th, td { padding:15px 12px; border-bottom:1px solid var(--border-color); text-align:left; vertical-align: top; }
    th { background: #f8fafc; color: var(--text-muted); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    tr:hover td { background-color: #f8fafc; }
    
    /* sub-category badge list */
    .sub-badge {
        display: inline-block;
        background: #f1f5f9;
        color: var(--primary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 4px 10px;
        font-size: 12px;
        margin: 4px 4px 0 0;
        font-weight: 500;
    }
    .no-sub { color: var(--text-muted); font-style: italic; font-size: 13px; }

    /* buttons */
    .button { padding:8px 16px; background: var(--primary); color:#fff !important; border:none; border-radius:6px; text-decoration:none; cursor:pointer; font-size:14px; font-weight:500; transition:all 0.2s; display:inline-block; }
    .button:hover { background: #3a0f3d; }
    .button.secondary { background: linear-gradient(135deg, #11998e, #38ef7d); color: white !important; }
    .button.secondary:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(17,153,142,0.3); }
    .button.danger { background: #ef4444; }
    .button.danger:hover { background: #dc2626; }
    .button.sm { padding: 6px 12px; font-size: 13px; margin-right: 5px; }

    /* modals */
    .modal-bg { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); display:none; justify-content:center; align-items:center; z-index:9999; backdrop-filter: blur(2px); }
    .modal-box { width:550px; max-width:95%; max-height: 90vh; overflow-y:auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2); border-top: 5px solid var(--primary); animation: fadeIn 0.2s ease-out; }
    .modal-box h3 { margin-top:0; margin-bottom: 25px; font-size: 22px; color: #0f172a; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
    
    input[type="text"] {
        width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; margin-top:5px; box-sizing: border-box; transition: border-color 0.2s; font-family: inherit;
    }
    input:focus { border-color: var(--primary); outline:none; box-shadow: 0 0 0 3px rgba(75, 19, 79, 0.1); }
    label { font-weight:600; display:block; margin-top:15px; color: var(--text-dark); font-size: 14px; }
    
    /* Subcategory rows */
    .sub-row { display: flex; align-items: center; gap: 8px; margin-top: 10px; }
    .sub-row input { flex: 1; margin-top: 0; }
    .sub-row .remove-sub { background: #fee2e2; border: none; color: #dc2626; border-radius: 4px; padding: 10px 14px; cursor: pointer; transition: 0.2s; font-weight: bold; }
    .sub-row .remove-sub:hover { background: #dc2626; color: white; }

    .add-sub-btn { margin-top: 15px; background: none; border: 2px dashed #cbd5e1; color: var(--text-muted); border-radius: 6px; padding: 10px; cursor: pointer; font-size: 14px; font-weight: 500; width: 100%; transition: all 0.2s; }
    .add-sub-btn:hover { border-color: var(--primary); color: var(--primary); background: #f8fafc; }

    .modal-footer { display: flex; gap: 10px; margin-top: 25px; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* responsive */
    @media (max-width:880px){
        .navbar { flex-direction: column; gap: 15px; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
    }
</style>

<script>
/* ── Utility ── */
function escHtml(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Modal helpers ── */
function openAddModal() {
    const list = document.getElementById('addSubList');
    list.innerHTML = '';
    addSubRow('addSubList', 'subcategories[]');
    document.getElementById('addModal').style.display = 'flex';
}

function openEditModal(btn) {
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    const subs = JSON.parse(btn.dataset.subs || '[]');

    document.getElementById('edit_id').value   = id;
    document.getElementById('edit_name').value = name;

    const list = document.getElementById('editSubList');
    list.innerHTML = '';
    subs.forEach(s => addExistingSubRow(s.id, s.name));

    document.getElementById('editModal').style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function deleteCategory(id) {
    if (confirm('Delete this category and all its sub-categories?')) {
        window.location.href = 'categories.php?delete=' + id;
    }
}

/* ── Add Sub-Category modal rows (new entries) ── */
function addSubRow(listId, inputName) {
    const list = document.getElementById(listId);
    const row  = document.createElement('div');
    row.className = 'sub-row';
    row.innerHTML = `
        <input type="text" name="${inputName}" class="form-control"
               placeholder="Sub-category name">
        <button type="button" class="remove-sub" onclick="this.parentElement.remove()" title="Remove">×</button>
    `;
    list.appendChild(row);
    row.querySelector('input').focus();
}

/* ── Existing sub-category row (carries hidden ID for update) ── */
function addExistingSubRow(subId, subName) {
    const list = document.getElementById('editSubList');
    const row  = document.createElement('div');
    row.className = 'sub-row';
    row.innerHTML = `
        <input type="hidden" name="sub_ids[]" value="${escHtml(subId)}">
        <input type="text"   name="sub_names[]" class="form-control"
               value="${escHtml(subName)}" placeholder="Sub-category name">
        <button type="button" class="remove-sub" onclick="this.parentElement.remove()" title="Remove">×</button>
    `;
    list.appendChild(row);
}
</script>
</head>
<body>

<div class="navbar">
    <a href="/partslo/admin/dashboard.php" class="brand">PartsLo Admin</a>
    <div class="nav-links">
        <a href="/partslo/index.php" target="_blank">View Store</a>
        <a href="/partslo/admin/dashboard.php">Dashboard</a>
        <a href="/partslo/admin/logout.php" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5;">Logout</a>
    </div>
</div>

<div class="container">
    <div class="page-header">
        <h1>Manage Categories</h1>
        <button class="button secondary" onclick="openAddModal()">+ Add Category</button>
    </div>

    <div class="table-box">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 25%;">Category Name</th>
                        <th style="width: 45%;">Sub-Categories</th>
                        <th style="width: 22%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= $cat['id'] ?></td>
                        <td><strong style="color:var(--primary); font-size:16px;"><?= htmlspecialchars($cat['name']) ?></strong></td>
                        <td>
                            <?php if (!empty($cat['subcats'])): ?>
                                <?php foreach (explode(', ', $cat['subcats']) as $sub): ?>
                                    <span class="sub-badge"><?= htmlspecialchars($sub) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="no-sub">No sub-categories attached</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                $catSubs = $subsByCategory[$cat['id']] ?? [];
                                $catSubsJson = htmlspecialchars(json_encode($catSubs), ENT_QUOTES);
                            ?>
                            <button class="button sm"
                                    data-id="<?= $cat['id'] ?>"
                                    data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                                    data-subs="<?= $catSubsJson ?>"
                                    onclick="openEditModal(this)">
                                Edit
                            </button>
                            <button class="button sm danger"
                                    onclick="deleteCategory(<?= $cat['id'] ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════
     ADD CATEGORY MODAL
     ══════════════════════════════════ -->
<div class="modal-bg" id="addModal">
    <div class="modal-box">
        <h3>➕ Add Category</h3>
        <form method="POST">
            <label>Category Name *</label>
            <input type="text" name="category_name" class="form-control"
                   placeholder="e.g. Motorbike" required>

            <label style="margin-top:18px;">Sub-Categories</label>
            <div id="addSubList">
                <!-- rows injected by JS -->
            </div>
            <button type="button" class="add-sub-btn" onclick="addSubRow('addSubList','subcategories[]')">+ Add Sub-Category</button>

            <div class="modal-footer">
                <button class="button" type="submit" name="add_category">Save Category</button>
                <button class="button" type="button"
                        onclick="closeModal('addModal')" style="background:#555;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════
     EDIT CATEGORY MODAL
     ══════════════════════════════════ -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <h3>✏️ Edit Category</h3>
        <form method="POST">
            <input type="hidden" id="edit_id" name="id">
            <label>Category Name *</label>
            <input type="text" id="edit_name" name="category_name" class="form-control" required>

            <label style="margin-top:18px;">Sub-Categories</label>
            <div id="editSubList">
                <!-- pre-populated by openEditModal() -->
            </div>
            <button type="button" class="add-sub-btn"
                    onclick="addSubRow('editSubList','new_subcategories[]')">+ Add Sub-Category</button>

            <div class="modal-footer">
                <button class="button" type="submit" name="edit_category">Update</button>
                <button class="button" type="button"
                        onclick="closeModal('editModal')" style="background:#555;">Cancel</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
