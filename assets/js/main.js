// ===============================
// LOAD PRODUCTS FROM DATABASE
// ===============================
function loadProducts(category = '', keyword = '', min = 0, max = 999999) {
    fetch(`tools/get_products.php?category=${category}&keyword=${keyword}&min=${min}&max=${max}`)
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById("productList");
        container.innerHTML = '';
        if(data.length === 0){
            container.innerHTML = '<p>No products found.</p>';
            return;
        }
        data.forEach(p => {
            container.innerHTML += `
            <div class="product-card">
                <img src="/partslo/assets/images/${p.image}" alt="${p.name}" style="height:200px;">

                <div class="product-title">${p.name}</div>
                <div class="product-category">${p.category_name}</div>
                <div class="product-price">Rs ${p.price}</div>
                <button class="view-btn" onclick="viewProduct(${p.id})">View Details</button>
            </div>`;
        });
    }).catch(err => console.error("Error loading products:", err));
}

// Initial load
loadProducts();

// ===============================
// CATEGORY BOX FILTER
// ===============================
document.querySelectorAll(".cat-box").forEach(box => {
    box.addEventListener("click", () => {
        const cat = box.dataset.cat;
        loadProducts(cat);
    });
});

// ===============================
// SEARCH BUTTON
// ===============================
document.getElementById("searchBtn").addEventListener("click", () => {
    const keyword = document.getElementById("searchInput").value;
    const min = parseInt(document.getElementById("minPrice").value) || 0;
    const max = parseInt(document.getElementById("maxPrice").value) || 999999;
    const cat = document.getElementById("categoryFilter").value;

    loadProducts(cat, keyword, min, max);
});

// ===============================
// VIEW PRODUCT
// ===============================
function viewProduct(id){
    window.location.href = `product/view.php?id=${id}`;
}
