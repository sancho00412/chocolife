const defaultProducts = [
    {
        productName: 'SPA & Massage',
        productNumber: '58241',
        price: '12,000 ₸',
        category: 'Beauty',
        status: 'Available'
    },
    {
        productName: 'Restaurant Dinner',
        productNumber: '73185',
        price: '8,500 ₸',
        category: 'Food',
        status: 'Available'
    },
    {
        productName: 'Cinema Tickets for Two',
        productNumber: '42673',
        price: '5,000 ₸',
        category: 'Entertainment',
        status: 'Available'
    },
    {
        productName: 'Beauty Salon',
        productNumber: '91524',
        price: '15,000 ₸',
        category: 'Beauty',
        status: 'Available'
    },
    {
        productName: 'Hotel Weekend',
        productNumber: '34862',
        price: '35,000 ₸',
        category: 'Travel',
        status: 'Available'
    },
    {
        productName: 'Fitness Club Membership',
        productNumber: '65731',
        price: '20,000 ₸',
        category: 'Fitness',
        status: 'Available'
    },
    {
        productName: 'Bowling Night',
        productNumber: '82416',
        price: '7,000 ₸',
        category: 'Entertainment',
        status: 'Available'
    },
    {
        productName: 'Water Park Family',
        productNumber: '39175',
        price: '18,000 ₸',
        category: 'Entertainment',
        status: 'Available'
    }
];

// Load products only once.
// Do not automatically add deleted products again.
function loadProducts() {
    try {
        const savedProducts = JSON.parse(
            localStorage.getItem('products')
        );

        if (Array.isArray(savedProducts)) {
            return savedProducts;
        }
    } catch (error) {
        console.error('Could not load products:', error);
    }

    localStorage.setItem(
        'products',
        JSON.stringify(defaultProducts)
    );

    return [...defaultProducts];
}
