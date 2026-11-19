import Alpine from 'alpinejs';
import announcement from './storefront/announcement';
import callbackModal from './storefront/callbackModal';
import cartDrawer from './storefront/cartDrawer';
import checkout from './storefront/checkout';
import copyText from './storefront/copyText';
import countdown from './storefront/countdown';
import deliveryEstimator from './storefront/deliveryEstimator';
import gallery from './storefront/gallery';
import megaMenu from './storefront/megaMenu';
import priceBox from './storefront/priceBox';
import searchBox from './storefront/searchBox';
import siteShell from './storefront/siteShell';

Alpine.data('announcement', announcement);
Alpine.data('callbackModal', callbackModal);
Alpine.data('cartDrawer', cartDrawer);
Alpine.data('checkout', checkout);
Alpine.data('copyText', copyText);
Alpine.data('countdown', countdown);
Alpine.data('deliveryEstimator', deliveryEstimator);
Alpine.data('gallery', gallery);
Alpine.data('megaMenu', megaMenu);
Alpine.data('priceBox', priceBox);
Alpine.data('searchBox', searchBox);
Alpine.data('siteShell', siteShell);

Alpine.store('cart', { count: Number(document.body.dataset.cartCount) || 0 });

window.Alpine = Alpine;
Alpine.start();
