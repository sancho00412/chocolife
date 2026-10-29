const savedOrders = (() => {
    try {
        const data = JSON.parse(localStorage.getItem('chocolifeOrders'));
        return Array.isArray(data) ? data : [];
    } catch {
        return [];
    }
})();

function formatOrderPrice(price) {
    return (Number(price) || 0).toLocaleString('en-US') + ' ₸';
}

const Orders = savedOrders.map(order => {
    const items = Array.isArray(order.items) ? order.items : [];

    const productNames = items
        .map(item => item.productName || 'Product')
        .join(', ');

    const productNumbers = items
        .map(item => item.productNumber)
        .filter(Boolean)
        .join(', ');

    return {
        productName: order.customerName || 'Customer',
        productNumber: order.id || 'N/A',
        paymentStatus: formatOrderPrice(order.total),
        shipping: order.status || 'Pending',
        customerEmail: order.email || '',
        customerPhone: order.phone || '',
        customerAddress: order.address || '',
        items: items,
        itemNames: productNames || 'No products',
        itemNumbers: productNumbers || 'N/A',
        createdAt: order.createdAt || ''
    };
});