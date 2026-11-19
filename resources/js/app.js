import Alpine from 'alpinejs';
import checkout from './storefront/checkout';
import gallery from './storefront/gallery';
import priceBox from './storefront/priceBox';
import searchBox from './storefront/searchBox';

Alpine.data('checkout', checkout);
Alpine.data('gallery', gallery);
Alpine.data('priceBox', priceBox);
Alpine.data('searchBox', searchBox);

window.Alpine = Alpine;
Alpine.start();
