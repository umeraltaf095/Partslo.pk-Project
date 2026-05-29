<?php
session_start();
require_once "../includes/db_connect.php";

// Protect admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Ensure upload folder exists
$uploadDir = __DIR__ . "/../assets/images/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// -------------------- HANDLE ADD PRODUCT --------------------
if (isset($_POST['add_product'])) {
    $category_id = intval($_POST['category_id'] ?? 0);
    $subcategory_id = intval($_POST['subcategory_id'] ?? 0);
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? number_format((float)$_POST['price'], 2, '.', '') : '0.00';
    $market_price = isset($_POST['market_price']) ? number_format((float)$_POST['market_price'],2,'.','') : null;
    $stock = intval($_POST['stock'] ?? 0);

    // Handle image upload (single image)
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $tmp = $_FILES['image']['tmp_name'];
        $orig = basename($_FILES['image']['name']);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $imageName = time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . $imageName;
            if (!move_uploaded_file($tmp, $dest)) {
                $imageName = null;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (category_id, subcategory_id, sku, name, description, price, market_price, stock, image, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$category_id, $subcategory_id, $sku, $name, $description, $price, $market_price, $stock, $imageName]);

    header("Location: products.php");
    exit;
}

// -------------------- HANDLE EDIT PRODUCT --------------------
if (isset($_POST['edit_product'])) {
    $id = intval($_POST['id']);
    $category_id = intval($_POST['category_id'] ?? 0);
    $subcategory_id = intval($_POST['subcategory_id'] ?? 0);
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? number_format((float)$_POST['price'],2,'.','') : '0.00';
    $market_price = isset($_POST['market_price']) ? number_format((float)$_POST['market_price'],2,'.','') : null;
    $stock = intval($_POST['stock'] ?? 0);

    // Fetch current product to maybe delete old image
    $cur = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $cur->execute([$id]);
    $old = $cur->fetch(PDO::FETCH_ASSOC);
    $oldImage = $old['image'] ?? null;

    // Handle image upload replacement
    $imageName = $oldImage;
    if (!empty($_FILES['image']['name'])) {
        $tmp = $_FILES['image']['tmp_name'];
        $orig = basename($_FILES['image']['name']);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $imageName = time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . $imageName;
            if (move_uploaded_file($tmp, $dest)) {
                // delete old image if present
                if ($oldImage && file_exists($uploadDir . $oldImage)) {
                    @unlink($uploadDir . $oldImage);
                }
            } else {
                // revert to old if failed
                $imageName = $oldImage;
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE products SET category_id=?, subcategory_id=?, sku=?, name=?, description=?, price=?, market_price=?, stock=?, image=? WHERE id=?");
    $stmt->execute([$category_id, $subcategory_id, $sku, $name, $description, $price, $market_price, $stock, $imageName, $id]);

    header("Location: products.php");
    exit;
}

// -------------------- HANDLE DELETE PRODUCT --------------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // fetch image to delete file
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if (!empty($row['image']) && file_exists($uploadDir . $row['image'])) {
            @unlink($uploadDir . $row['image']);
        }
        $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $del->execute([$id]);
    }
    header("Location: products.php");
    exit;
}

// -------------------- FETCH PRODUCTS + CATEGORIES + SUBCATEGORIES --------------------
$productsStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, s.name AS subcategory_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    ORDER BY p.id DESC
");
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

// load categories
$catsStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catsStmt->fetchAll(PDO::FETCH_ASSOC);

// load subcategories
$subStmt = $pdo->query("SELECT id, category_id, name, slug FROM subcategories ORDER BY name ASC");
$subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Manage Products</title>
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
    .container { max-width:1300px; margin:40px auto; padding: 0 20px; }

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
    table { width:100%; border-collapse:collapse; min-width: 900px; }
    th, td { padding:15px 12px; border-bottom:1px solid var(--border-color); text-align:left; }
    th { background: #f8fafc; color: var(--text-muted); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    tr:hover td { background-color: #f8fafc; }
    
    .img-thumb { height:45px; width:45px; border-radius:6px; object-fit:cover; border:1px solid var(--border-color); display: block; }
    
    .price-tag { font-weight: 600; color: #10b981; }

    /* buttons */
    .button { padding:8px 16px; background: var(--primary); color:#fff; border:none; border-radius:6px; text-decoration:none; cursor:pointer; font-size:14px; font-weight:500; transition:all 0.2s; display:inline-block; }
    .button:hover { background: #3a0f3d; }
    .button.secondary { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
    .button.secondary:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(17,153,142,0.3); }
    .button.danger { background: #ef4444; }
    .button.danger:hover { background: #dc2626; }
    .button.sm { padding: 6px 12px; font-size: 13px; margin-right: 5px; }

    /* modals */
    .modal-bg { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); display:none; justify-content:center; align-items:center; z-index:9999; backdrop-filter: blur(2px); }
    .modal-box { width:700px; max-width:95%; max-height: 90vh; overflow-y:auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2); border-top: 5px solid var(--primary); animation: fadeIn 0.2s ease-out; }
    .modal-box h3 { margin-top:0; margin-bottom: 25px; font-size: 22px; color: #0f172a; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
    
    .modal-row { display:flex; gap:20px; }
    .modal-col { flex:1; }
    input[type="text"], input[type="number"], input[type="file"], textarea, select {
        width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; margin-top:5px; box-sizing: border-box; transition: border-color 0.2s; font-family: inherit;
    }
    input:focus, textarea:focus, select:focus { border-color: var(--primary); outline:none; box-shadow: 0 0 0 3px rgba(75, 19, 79, 0.1); }
    label { font-weight:600; display:block; margin-top:15px; color: var(--text-dark); font-size: 14px; }
    .small { font-size:12px; color:var(--text-muted); margin-top:5px; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* responsive */
    @media (max-width:880px){
        .modal-row { flex-direction:column; gap:0; }
        .navbar { flex-direction: column; gap: 15px; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
    }
</style>

<script>
const subcategories = <?php
    // build map: category_id => [{id,name},...]
    $map = [];
    foreach($subcategories as $s) {
        $map[$s['category_id']][] = ['id'=>$s['id'],'name'=>$s['name']];
    }
    echo json_encode($map);
?>;

// open add modal
function openAddModal() {
    // reset fields
    document.getElementById('addForm').reset();
    // populate category/subcategory
    populateSubcats('add_category_id','add_subcategory_id');
    document.getElementById('addModal').style.display = 'flex';
}

// open edit modal and populate values
function openEditModal(p){
    // p is product object
    document.getElementById('edit_id').value = p.id;
    document.getElementById('edit_sku').value = p.sku || '';
    document.getElementById('edit_name').value = p.name || '';
    document.getElementById('edit_description').value = p.description || '';
    document.getElementById('edit_price').value = p.price || '';
    document.getElementById('edit_market_price').value = p.market_price || '';
    document.getElementById('edit_stock').value = p.stock || 0;
    // set category then subcat
    document.getElementById('edit_category_id').value = p.category_id || '';
    populateSubcats('edit_category_id','edit_subcategory_id', p.subcategory_id);
    // image preview
    if (p.image) {
        document.getElementById('edit_image_preview').src = '/partslo/assets/images/' + p.image;
        document.getElementById('edit_image_preview').style.display = 'block';
    } else {
        document.getElementById('edit_image_preview').style.display = 'none';
    }
    document.getElementById('editModal').style.display = 'flex';
}

// close modal
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// populate subcategory select based on category select id values
function populateSubcats(categorySelectId, subcatSelectId, selectedSubId = null) {
    const catSel = document.getElementById(categorySelectId);
    const subSel = document.getElementById(subcatSelectId);
    const catId = catSel.value;
    // clear
    subSel.innerHTML = '<option value="">-- Select Subcategory --</option>';
    if (!catId) return;
    const list = subcategories[catId] || [];
    list.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.text = s.name;
        if (selectedSubId && selectedSubId == s.id) opt.selected = true;
        subSel.appendChild(opt);
    });
}

// when category changes in add form
function onAddCategoryChange() {
    populateSubcats('add_category_id','add_subcategory_id');
}
// edit category change
function onEditCategoryChange() {
    populateSubcats('edit_category_id','edit_subcategory_id');
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
        <h1>Manage Products</h1>
        <button class="button secondary" onclick="openAddModal()">+ Add New Product</button>
    </div>

    <div class="table-box">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Image</th>
                        <th style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id']) ?></td>
                        <td>
                            <strong style="color:var(--primary);"><?= htmlspecialchars($p['name']) ?></strong><br>
                            <span style="font-size:12px; color:var(--text-muted);">SKU: <?= htmlspecialchars($p['sku']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['subcategory_name'] ?? '-') ?></td>
                        <td class="price-tag">Rs <?= number_format($p['price'],2) ?></td>
                        <td>
                            <?php if(intval($p['stock']) <= 5): ?>
                                <span style="color: #ef4444; font-weight:600;"><?= intval($p['stock']) ?> (Low)</span>
                            <?php else: ?>
                                <?= intval($p['stock']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../assets/images/' . $p['image'])): ?>
                                <img class="img-thumb" src="/partslo/assets/images/<?= htmlspecialchars($p['image']) ?>" alt="">
                            <?php else: ?>
                                <span class="small" style="font-style:italic;">No image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="button sm" onclick='openEditModal(<?= json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP) ?>)'>Edit</button>
                            <a class="button sm danger" href="products.php?delete=<?= intval($p['id']) ?>" onclick="return confirm('Delete this product?')">Del</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ------------------ ADD PRODUCT MODAL ------------------- -->
<div class="modal-bg" id="addModal">
    <div class="modal-box">
        <h3>Add Product</h3>
        <form id="addForm" method="POST" enctype="multipart/form-data">
            <div class="modal-row">
                <div class="modal-col">
                    <label>Category</label>
                    <select id="add_category_id" name="category_id" onchange="onAddCategoryChange()" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Subcategory</label>
                    <select id="add_subcategory_id" name="subcategory_id">
                        <option value="">-- Select Subcategory --</option>
                    </select>

                    <label>SKU</label>
                    <input type="text" name="sku" placeholder="SKU">

                    <label>Name</label>
                    <input type="text" name="name" required>

                    <label>Price</label>
                    <input type="number" step="0.01" name="price" required>

                    <label>Market Price</label>
                    <input type="number" step="0.01" name="market_price">

                    <label>Stock</label>
                    <input type="number" name="stock" value="0" required>
                </div>

                <div class="modal-col">
                    <label>Description</label>
                    <textarea name="description" rows="10"></textarea>

                    <label>Product Image (single)</label>
                    <input type="file" name="image" accept="image/*">
                    <p class="small">Allowed: jpg, jpeg, png, gif, webp</p>
                </div>
            </div>

            <div style="text-align:right;margin-top:12px;">
                <button class="button" type="submit" name="add_product">Add Product</button>
                <button class="button" type="button" onclick="closeModal('addModal')">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- ------------------ EDIT PRODUCT MODAL ------------------- -->
<div class="modal-bg" id="editModal">
    <div class="modal-box">
        <h3>Edit Product</h3>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="edit_id" name="id">
            <div class="modal-row">
                <div class="modal-col">
                    <label>Category</label>
                    <select id="edit_category_id" name="category_id" onchange="onEditCategoryChange()" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>Subcategory</label>
                    <select id="edit_subcategory_id" name="subcategory_id">
                        <option value="">-- Select Subcategory --</option>
                    </select>

                    <label>SKU</label>
                    <input id="edit_sku" type="text" name="sku" placeholder="SKU">

                    <label>Name</label>
                    <input id="edit_name" type="text" name="name" required>

                    <label>Price</label>
                    <input id="edit_price" type="number" step="0.01" name="price" required>

                    <label>Market Price</label>
                    <input id="edit_market_price" type="number" step="0.01" name="market_price">

                    <label>Stock</label>
                    <input id="edit_stock" type="number" name="stock" value="0" required>
                </div>

                <div class="modal-col">
                    <label>Description</label>
                    <textarea id="edit_description" name="description" rows="10"></textarea>

                    <label>Current Image</label>
                    <img id="edit_image_preview" src="" alt="" style="height:120px;display:none;border-radius:6px;margin-bottom:8px;object-fit:cover;">

                    <label>Replace Image</label>
                    <input type="file" name="image" accept="image/*">
                    <p class="small">Leave empty to keep existing image.</p>
                </div>
            </div>

            <div style="text-align:right;margin-top:12px;">
                <button class="button" type="submit" name="edit_product">Save Changes</button>
                <button class="button" type="button" onclick="closeModal('editModal')">Close</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
