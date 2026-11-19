# MitekStore — Pages Blueprint (benchmark: iris.ma)

> Put this file at `docs/pages-blueprint.md`. Read it together with `CLAUDE.md` and `Mitek.md`.
> iris.ma (SGM2I, Casablanca/Témara) is the main competitor. It was reviewed on 19 Nov 2026.
> We reuse **UX patterns and page structure only**. Never copy their text, images, logo, colors, testimonials, figures or layout pixel-for-pixel.
> All copy comes from `Mitek.md` or stays a marked `TODO`. Never invent stats, reviews, client names or legal numbers.

---

## 0. Positioning: where Mitek must differ

| | iris.ma | Mitek.ma |
|---|---|---|
| Catalogue | ~4,000 IT & electronics products (PCs, TV, gaming, phones) | Office supplies + consumables + IT accessories + furniture + hygiene |
| Services | Sales only (plus warranty/SAV) | **IT services** (maintenance, networks, cybersecurity, cloud, data recovery…) = our differentiator |
| Payment | Card (Payzone), transfer, cash, cheque | Transfer, cash deposit, COD ≤ 2,000 DH TTC, certified cheque (no card for now) |
| Pickup | Store in Casablanca + Témara pickup point | Casablanca (address `TODO`) |
| Tone | "L'expert tech", promo-heavy (Black Friday everywhere) | Reliable B2B partner for offices: calm, professional, fewer flashing promos |

The message to land everywhere is **"Tout pour votre bureau : fournitures, matériel et services informatiques, un seul interlocuteur."**

---

## 1. Global layout (every storefront page)

### Top bar (thin, dark navy)
- Left: WhatsApp link, email (`contact@mitek.ma`) with a copy-to-clipboard button
- Right: "Contactez-nous", **"Être rappelé"** (callback request modal: name, phone, best time slot, subject), language switcher placeholder (FR now, AR later)
- An optional dismissible announcement bar above it (driven from admin settings: text, link, active dates)

### Header (sticky, white)
- Logo, then a **search bar** in the centre (autocomplete across products, references, brands, compatible printer models, categories and services), then "Mon compte" and "Panier" (count badge plus a mini-cart drawer)
- Navigation row:
  - **"Tous nos produits"** mega menu: the 5 families, then subcategories with product counts, plus a featured block
  - Quick links: Consommables & toners · Informatique · Bureau & papeterie · Mobilier · Hygiène · **Services**
  - Right side: **"Espace pro"** (`/pro`), **"Demander un devis"** (`/devis`), WhatsApp icon
- Mobile: hamburger, a full-screen drawer with accordion categories, and the search on its own row

### Floating WhatsApp button
- Bottom-left, with "Discuter sur WhatsApp" and the opening hours from settings. Hide it when the number is empty.

### Pre-footer
1. **Reassurance strip** with 4 cards, each linking to its page:
   - Livraison partout au Maroc (24–72 h)
   - 4 moyens de paiement (virement, dépôt, COD, chèque)
   - Facture avec ICE pour entreprises
   - Services informatiques sur site
2. **Delivery estimator**: "Estimez votre délai de livraison pour votre ville" (city select, which returns the delay and the fee from the shipping grid)
3. **Newsletter band**: e-mail field, CTA, and the **law 09-08 / CNDP notice** (CNDP number `TODO`). A first-order incentive is optional; put it behind a setting and keep it off by default.
4. **Contact block** with 2 cards:
   - "Une question ? Contactez-nous": WhatsApp, phone, email, quote, order tracking
   - "Venez nous voir": address, hours, Google Maps button (all from settings)

### Footer (dark navy)
- Column 1: logo, one-line pitch, social icons (from settings)
- **Acheter**: promotions, best sellers, new arrivals, all brands, toner finder, site map
- **Services**: links to the service pages
- **Aide & commande**: delivery, payment, order tracking, invoices, FAQ, contact
- **Mitek.ma**: about, Espace pro, legal pages
- **Payment block**: 4 cards (transfer, deposit, COD, cheque) with one line each
- **Company identity block** ("Une société en règle"): STE MITEK SARL AU, RC, IF, ICE, TP, CNSS from settings, each hidden while empty. This builds B2B trust, as iris does.
- Copyright line

---

## 2. Home `/`  (design-taste-frontend + better-ui)

1. **Hero carousel** (admin-managed slides: title, subtitle, CTA, image, link, dates, order). 3–5 slides max. Autoplay must be pausable and the carousel accessible. Include an LCP-optimised first image. Default slides: products / services / Espace pro.
2. **"Choisissez votre univers"**: a grid of category tiles (image, name, **product count**) plus a dark "Tout le catalogue: N produits" tile.
3. **"Nos services informatiques"**: 4–6 service cards with an icon, a one-line benefit and a CTA "Demander une intervention". *Iris doesn't have this, so it is ours to own.*
4. **"Bons plans du moment"**: a product carousel of items with a compare-at price (badge −x %, "Économisez x DH", optional end date).
5. **"Nos meilleures ventes"**: tabs per family, each showing a product grid.
6. **Toner finder**: "Trouvez votre cartouche": type a printer model, get compatible consumables (uses the compatibility relation).
7. **"Pourquoi Mitek ?"**: the 5 points from `Mitek.md`.
8. **Espace pro teaser**: invoice with ICE, quote within 24 h, tier pricing, dedicated contact, linking to `/pro`.
9. **"Nos marques partenaires"**: a logo strip linking to brand pages.
10. **FAQ excerpt**: 3–4 items (FAQPage JSON-LD).
11. **SEO text block**: about 150 words, collapsible ("Lire la suite").
Then the pre-footer and footer.

---

## 3. Category listing `/categories/{slug}`

- Breadcrumb, **H1 + "(N références)"**
- **SEO intro** (from the category description, collapsible "Lire la suite")
- **Subcategory chips** with counts
- Optional "guide" callout (link to a buying guide CMS page, e.g. "Toner : trouver la bonne référence")
- **Highlight tabs**: Meilleures ventes / Nouveautés / Top promos (6–8 cards, horizontal scroll)
- Layout: **filters sidebar** (drawer on mobile) next to the results
  - Sous-catégories (with counts)
  - Marque (checkbox list with an inline search, "Voir plus")
  - Prix (MAD) (min/max inputs; a range slider is optional, Alpine)
  - Disponibilité: "En stock uniquement"
  - **Imprimante compatible** (consumables categories only, with an inline search)
  - A small "?" tooltip on each facet
- Results toolbar: "Filtrer dans ces N références" (text filter), "Trier par" (pertinence, prix ↑↓, nouveautés, meilleures ventes), active filter pills with "Tout effacer"
- **Product card**:
  - Badges (promo %, top vente, nouveau)
  - Image
  - **Brand**
  - Title (2 lines)
  - Reference
  - 2–3 key spec bullets
  - Stock status
  - Price **TTC** (plus a small HT line), and the tier hint "dès x DH par 10"
  - Add-to-cart icon button
  - **"Commander par WhatsApp"** link (prefilled message with the product name, reference, price and URL)
- Pagination (crawlable links). Filters must stay GET params (see SEO rules: noindex for multi-filter/sort).

---

## 4. Product page `/produit/{slug}`

**Above the fold (2 columns):**
- Left: gallery (zoom on hover/click, thumbnails, swipe on mobile)
- Right:
  - Brand link ("Voir la marque X")
  - **H1** with the reference
  - A one-paragraph summary
  - 3–4 key bullet points plus "Voir les N caractéristiques" (anchor link)
  - Copyable reference
  - **Price box**: TTC large, HT small, compare-at price struck through with the % saved, and the **tier price table** (quantity → unit price HT/TTC)
  - Stock status
  - Quantity stepper
  - **Two CTAs**: "Ajouter au panier" (primary) and **"Commander sur WhatsApp"** (green, prefilled)
  - **"Comment l'obtenir"** card:
    - "Livraison partout au Maroc", showing an estimated date range plus the fee, and "Indiquer ma ville" (opens the city estimator)
    - Pickup in Casablanca: only if enabled in settings
  - **COD eligibility hint**: "Paiement à la livraison disponible jusqu'à 2 000 DH TTC"
  - Warranty line (from the product or category setting)
  - Quote shortcut: "Besoin d'une grande quantité ? Demander un devis" (prefills `/devis` with the product)

**Below:**
- **Sticky section nav**: Présentation · Caractéristiques · Compatibilité · FAQ · Avis
- Présentation / Description (rich text)
- Caractéristiques (spec table)
- **Compatibilité** (consumables): a list of compatible printers, each linking to search
- **Product FAQ** (optional per product, with FAQPage JSON-LD)
- **Avis clients**: phase 2 (see the backlog); for now leave an empty placeholder or hide it
- Tabs: Produits similaires / Meilleures ventes de la catégorie
- **Mobile sticky buy bar** (price plus "Ajouter au panier")

---

## 5. Services

### `/services` (marketing page, design-taste-frontend)
- Hero: "Vos experts informatiques à Casablanca et partout au Maroc" plus CTA "Demander une intervention" and WhatsApp
- A grid of the 9 services (icon, title, 1-line benefit, link)
- **"Comment ça marche"**: 4 steps (demande → diagnostic → devis → intervention)
- **Contrats de maintenance pour entreprises** (monthly contracts, from `Mitek.md`) with a CTA
- "Pourquoi nous confier votre informatique" (from `Mitek.md`: certified team, on-site or remote…)
- FAQ excerpt, then the service request form (short version)

### `/services/{slug}`
- Breadcrumb, H1, intro, **"Ce que nous faisons"** (bullets from `Mitek.md`), "Pour qui", "Déroulement", related products (e.g. the network service shows switches and access points), FAQ, and the **request form** (sticky on desktop) with the service preselected. Service JSON-LD.

---

## 6. Espace pro `/pro` (inspired by iris.ma/pro, our own wording)

1. Hero: "Le partenaire bureau & informatique des entreprises marocaines" plus CTA "Demander un devis"
2. **Key figures**: only real numbers, so `TODO`, hidden while empty. Never invent them.
3. **"Tout ce qu'une entreprise attend"**, an 8-card grid:
   - Devis gratuit sous 24 h ouvrées
   - Facture au nom de la société avec ICE
   - Documents administratifs sur demande (RC, IF, ICE, RIB, attestation de régularité fiscale)
   - **Facture pro-forma** (see the backlog)
   - Paiements adaptés aux pros
   - Tarifs dégressifs par quantité
   - Livraison partout au Maroc
   - Interlocuteur dédié
4. **Services informatiques & contrats de maintenance**, linking to `/services`
5. **"Une société en règle, vérifiable"**: the legal identity block from settings
6. Partner brands
7. Testimonials: hidden until real ones exist (admin-managed)
8. **B2B FAQ**: ICE invoice, pro-forma, payment methods, VAT-exempt organisations, delivery, administrations and public tenders, opening a pro account
9. Final CTA band: quote form, WhatsApp, email

---

## 7. Quote `/devis` (standalone, not only from the cart)

- Fields:
  - Nom de la société
  - Nom du contact
  - Email
  - Téléphone
  - Ville
  - ICE (optional)
  - "Références précises ou description du projet : quantités, contraintes, délais…"
  - **File upload** (PDF, XLSX, images; max size and count set in config; stored privately)
  - Honeypot plus rate limit
- If it comes from a product or the cart, prefill the lines.
- Side panel: "Réponse sous 24 h ouvrées", WhatsApp (fastest), email, link to `/pro`
- Feeds the existing admin Devis screen (add support for attachments).

---

## 8. Contact `/contact`

- H1, a short intro, then 3 quick cards (WhatsApp, phone, email) with the hours
- The form (already built in Phase 5), plus the "Être rappelé" option
- Address plus the map embed and an "Itinéraire Google Maps" button
- Links to the FAQ, order tracking, quote and services

---

## 9. Other pages

- **À propos** `/a-propos`: mission, values (Fiabilité, Proximité, Innovation), what we do (products + services), the legal identity block, CTA. Use design-taste-frontend.
- **Pourquoi Mitek** (can merge into À propos)
- **Promotions** `/bons-plans`: all products with a compare-at price, plus the filters
- **Nouveautés** `/nouveautes`, **Meilleures ventes** `/meilleures-ventes`
- **Marques** `/marques` (A–Z grid) and `/marques/{slug}` (logo, intro, products with filters)
- **Toner finder** `/trouver-ma-cartouche`: search by printer model, results grouped by brand
- **Suivi de commande** `/suivi`: order number plus email, which returns the status timeline and tracking (no login required)
- **Modes de paiement**, **Modes de livraison**, **FAQ**, **CGV**, **Mentions légales**, **Confidentialité** (CMS, Phase 5)
- **Plan du site** `/plan-du-site` (HTML sitemap of categories, brands, services and pages)
- **404/500** (Phase 5)

---

## 10. Backlog surfaced by the benchmark (not in phases 1–5)

**P1: do now (cheap, high B2B value)**
1. "Commander par WhatsApp" on cards and the product page (prefilled `wa.me` link, number from settings)
2. "Être rappelé" callback requests (model, admin screen, notification)
3. `/devis` standalone with attachments
4. Delivery estimate per city (from the shipping grid) on the product page, cart and footer widget
5. Compare-at price, promo badges and an optional promo end date with a countdown
6. Product counts on category tiles and facets; the "Imprimante compatible" facet
7. `/pro` page, legal identity block, announcement bar
8. Public order tracking `/suivi`
9. Newsletter subscription with 09-08 consent (double opt-in, admin export)

**P2: after launch**
- **Pro-forma invoice PDF** generated from a cart or quote (`PF-2026-xxxxx`, not an invoice and no numbering constraints)
- Priced quotes (DV numbering, accept → order), already in the TODO list
- Customer reviews with moderation (and AggregateRating JSON-LD only when real reviews exist)
- Buying guides / blog (SEO content)
- Product comparator, quick-view modal
- Arabic (and English) storefront with hreflang
- Loyalty programme (cagnotte), card payment (CMI/Payzone)

---

## 11. Rules for Claude Code

- Storefront: Blade + Alpine only, server-rendered; admin: Inertia + Vue. Never Livewire.
- Use `design-taste-frontend` only on the marketing pages (home, services overview, about, pro). Use `better-ui` everywhere. The conflict resolutions in `CLAUDE.md` win.
- Every new admin-editable thing (slides, announcement bar, testimonials, social links, legal identity, callback requests, newsletter) gets an admin screen and settings, never hard-coded values.
- Every page: meta title/description, canonical, breadcrumb plus JSON-LD per `CLAUDE.md`.
- Before each page, propose its section structure and component list. Reuse existing Blade components and create the missing ones in `resources/views/components`.
- Check every page at 375 / 768 / 1280 px before reporting done.