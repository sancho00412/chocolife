// Admin offer form: validation, image preview and saving with an image upload

const productForm = document.querySelector('#product-form');

if (productForm) {
    const formMessage = document.querySelector('#form-message');
    const saveButton = document.querySelector('#save-button');
    const imageInput = document.querySelector('#image');
    const imagePreview = document.querySelector('#image-preview');

    const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    function setFieldError(field, message) {
        const element = document.querySelector(`#${field}-error`);

        if (element) {
            element.textContent = message;
        }
    }

    // Shows the chosen picture before it is uploaded
    imageInput.addEventListener('change', () => {
        const file = imageInput.files[0];
        setFieldError('image', '');

        if (!file) {
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            setFieldError('image', 'Only JPG, PNG or WEBP images are allowed.');
            imageInput.value = '';
            return;
        }

        if (file.size > MAX_IMAGE_SIZE) {
            setFieldError('image', 'The image must be at most 2 MB.');
            imageInput.value = '';
            return;
        }

        imagePreview.src = URL.createObjectURL(file);
        imagePreview.hidden = false;
    });

    // Client-side validation, the server checks the same rules again
    function validate(data) {
        const errors = {};
        const price = Number(data.get('price'));
        const oldPrice = data.get('oldPrice');
        const stock = data.get('stock');

        if (data.get('name').trim().length < 3) {
            errors.name = 'Name must be at least 3 characters.';
        }
        if (!/^[A-Za-z0-9-]{1,20}$/.test(data.get('productNumber').trim())) {
            errors.productNumber = 'Use 1-20 letters, digits or "-".';
        }
        if (data.get('price') === '' || !(price >= 0)) {
            errors.price = 'Enter a price of 0 or more.';
        }
        if (oldPrice !== '' && Number(oldPrice) < price) {
            errors.oldPrice = 'Old price cannot be lower than the price.';
        }
        if (!/^\d+$/.test(stock)) {
            errors.stock = 'Stock must be a whole number.';
        }
        if (!/^([1-9]|10)$/.test(data.get('maxPerCustomer'))) {
            errors.maxPerCustomer = 'Limit must be a whole number from 1 to 10.';
        }
        if (data.get('validUntil') && data.get('useUntil') && data.get('useUntil') < data.get('validUntil')) {
            errors.useUntil = 'The coupon cannot expire before the sale ends.';
        }
        if (!data.get('categoryId')) {
            errors.categoryId = 'Choose a category.';
        }

        return errors;
    }

    productForm.addEventListener('submit', async event => {
        event.preventDefault();

        document.querySelectorAll('.field-error').forEach(element => {
            element.textContent = '';
        });
        formMessage.textContent = '';
        formMessage.classList.remove('error');

        const data = new FormData(productForm);
        const errors = validate(data);

        if (Object.keys(errors).length > 0) {
            Object.entries(errors).forEach(([field, message]) => setFieldError(field, message));
            formMessage.textContent = 'Please fix the errors in the form.';
            formMessage.classList.add('error');
            return;
        }

        saveButton.disabled = true;
        formMessage.textContent = 'Saving...';

        try {
            // FormData is sent as multipart/form-data, so the image file is uploaded too
            const response = await fetch('api/admin-products.php', { method: 'POST', body: data });
            const result = await response.json();

            if (!result.success) {
                Object.entries(result.fields || {}).forEach(([field, message]) => setFieldError(field, message));
                formMessage.textContent = result.error;
                formMessage.classList.add('error');
                return;
            }

            window.location.href = `admin-products.php?saved=${result.created ? 'created' : 'updated'}`;
        } catch {
            formMessage.textContent = 'Could not connect to the server.';
            formMessage.classList.add('error');
        } finally {
            saveButton.disabled = false;
        }
    });
}
