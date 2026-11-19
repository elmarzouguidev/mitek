import Alpine from 'alpinejs';
import announcement from './storefront/announcement';
import checkout from './storefront/checkout';
import copyText from './storefront/copyText';
import gallery from './storefront/gallery';
import megaMenu from './storefront/megaMenu';
import priceBox from './storefront/priceBox';
import searchBox from './storefront/searchBox';
import siteShell from './storefront/siteShell';

Alpine.data('announcement', announcement);
Alpine.data('checkout', checkout);
Alpine.data('copyText', copyText);
Alpine.data('gallery', gallery);
Alpine.data('megaMenu', megaMenu);
Alpine.data('priceBox', priceBox);
Alpine.data('searchBox', searchBox);
Alpine.data('siteShell', siteShell);

window.Alpine = Alpine;
Alpine.start();
