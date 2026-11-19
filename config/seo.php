<?php

return [
    /*
    | Where the generated sitemap is written. The `sitemap:generate` command and the publish hooks
    | write it; GET /sitemap.xml serves it (and builds it on first request).
    */
    'sitemap_path' => env('SITEMAP_PATH', public_path('sitemap.xml')),

    /*
    | Paths crawlers must not visit (private areas and non-content pages).
    */
    'disallow' => [
        '/admin',
        '/compte',
        '/panier',
        '/commande',
        '/devis',
        '/recherche',
        '/connexion',
        '/inscription',
        '/mot-de-passe-oublie',
        '/reinitialiser-mot-de-passe',
        '/email',
    ],
];
