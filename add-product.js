if (localStorage.getItem('chocolifeUserRole') !== 'admin') {
    window.location.href = 'login.html';
}

const productForm = document.querySelector('#product-form');

let editProduct = null;

// Edit mode only when opened from the Products table.
if (new URLSearchParams(window.location.search).has('edit')) {
    try {
        editProduct = JSON.parse(localStorage.getItem('editProduct'));
    } catch {
        editProduct = null;
    }
} else {
    localStorage.removeItem('editProduct');
}

if (editProduct) {

    document.querySelector('h1').textContent = 'Edit Product';
    document.querySelector('.add-product-button').textContent = 'Save Changes';

    document.querySelector('#product-name').value = editProduct.productName || '';
    document.querySelector('#product-number').value = editProduct.productNumber || '';
    document.querySelector('#product-price').value =
        String(editProduct.price ?? '').replace(/[^\d.]/g, '');
    document.querySelector('#product-category').value = editProduct.category || '';
    document.querySelector('#product-description').value = editProduct.description || '';
    document.querySelector('#product-status').value = editProduct.status || 'Available';

}

function saveProduct(image) {

    const product = {
        productName: document.querySelector('#product-name').value.trim(),
        productNumber: document.querySelector('#product-number').value.trim(),
        price: Number(document.querySelector('#product-price').value).toLocaleString('en-US') + ' ₸',
        category: document.querySelector('#product-category').value,
        description: document.querySelector('#product-description').value.trim(),
        status: document.querySelector('#product-status').value,
        image
    };

    let products;

    try {
        products = JSON.parse(localStorage.getItem('products'));
        if (!Array.isArray(products)) products = [];
    } catch {
        products = [];
    }

    const index = editProduct
        ? products.findIndex(item =>
            String(item.productNumber) === String(editProduct.productNumber))
        : -1;

    const duplicate = products.some((item, i) =>
        i !== index && String(item.productNumber) === product.productNumber
    );

    if (duplicate) {
        alert('A product with this number already exists.');
        return;
    }

    if (index !== -1) {
        products[index] = product;
    } else {
        products.push(product);
    }

    try {
        localStorage.setItem('products', JSON.stringify(products));
    } catch {
        alert('Could not save the product. The image may be too large.');
        return;
    }

    if (editProduct) {
        localStorage.removeItem('editProduct');
        alert('Product updated successfully!');
        window.location.href = 'product.html';
        return;
    }

    alert('Product added successfully!');

    productForm.reset();
}

productForm.addEventListener('submit', (event) => {

    event.preventDefault();

    const imageFile = document.querySelector('#product-image').files[0];

    // Image is optional: keep the old one when editing.
    if (!imageFile) {
        saveProduct(editProduct ? editProduct.image || '' : '');
        return;
    }

    const reader = new FileReader();

    reader.onload = () => saveProduct(reader.result);
    reader.onerror = () => alert('Could not read the image file.');

    reader.readAsDataURL(imageFile);

});
