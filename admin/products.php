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

<style>
/* top bar */
.top-bar {
    background: #111;
    color: #fff;
    padding: 12px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.top-bar a { color: #fff; text-decoration:none; margin-left:12px; }
.top-bar .store-name { color:#00c3ff; font-weight:bold; font-size:18px; }

/* container */
.container { max-width:1100px; margin:28px auto; }

/* card */
.table-box {
    width:100%;
    background:#fff;
    padding:18px;
    border-radius:8px;
    box-shadow:0 0 12px rgba(0,0,0,0.12);
}

/* table */
table { width:100%; border-collapse:collapse; table-layout:fixed; }
th, td { padding:10px 12px; border-bottom:1px solid #eee; text-align:left; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
th:nth-child(1), td:nth-child(1) { width:6%; }
th:nth-child(2), td:nth-child(2) { width:22%; } /* name */
th:nth-child(3), td:nth-child(3) { width:12%; } /* category */
th:nth-child(4), td:nth-child(4) { width:12%; } /* subcat */
th:nth-child(5), td:nth-child(5) { width:10%; } /* price */
th:nth-child(6), td:nth-child(6) { width:8%; }  /* stock */
th:nth-child(7), td:nth-child(7) { width:12%; } /* image */
th:nth-child(8), td:nth-child(8) { width:18%; } /* actions */

.img-thumb { height:50px; border-radius:4px; object-fit:cover; }

/* buttons */
.button { padding:8px 12px; background:#111; color:#fff; border-radius:6px; text-decoration:none; cursor:pointer; display:inline-block; margin-right:6px; }
.button.secondary { background:#00aaff; }
.button.danger { background:#d00000; }

/* modals */
.modal-bg { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); display:none; justify-content:center; align-items:center; z-index:999; }
.modal-box { width:680px; max-width:95%; background:#fff; padding:18px; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.2); }
.modal-row { display:flex; gap:12px; }
.modal-col { flex:1; }
input[type="text"], input[type="number"], input[type="file"], textarea, select {
    width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:14px;
}
label { font-weight:600; display:block; margin-top:8px; }
.small { font-size:13px; color:#666; }

/* responsive */
@media (max-width:880px){
    .modal-row { flex-direction:column; }
    th, td { font-size:13px; }
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

<div class="top-bar">
    <div class="store-name">PartsLo Admin</div>
    <div>
        <a href="/partslo/index.php">Home</a>
        <a href="/partslo/admin/dashboard.php">Dashboard</a>
        <a href="/partslo/user/logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="table-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2 style="margin:0">Products</h2>
            <div>
                <button class="button secondary" onclick="openAddModal()">+ Add Product</button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product (SKU - Name)</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['id']) ?></td>
                    <td><?= htmlspecialchars($p['sku']) ?> — <?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['category_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['subcategory_name'] ?? '') ?></td>
                    <td>Rs <?= number_format($p['price'],2) ?></td>
                    <td><?= intval($p['stock']) ?></td>
                    <td>
                        <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../assets/images/' . $p['image'])): ?>
                            <img class="img-thumb" src="/partslo/assets/images/<?= htmlspecialchars($p['image']) ?>" alt="">
                        <?php else: ?>
                            <span class="small">No image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button" onclick='openEditModal(<?= json_encode($p, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP) ?>)'>Edit</button>
                        <a class="button danger" href="products.php?delete=<?= intval($p['id']) ?>" onclick="return confirm('Delete this product?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

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
