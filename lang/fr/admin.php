<?php

return [
    'products' => [
        'created' => 'Produit créé. Vous pouvez maintenant ajouter des images.',
        'updated' => 'Produit enregistré.',
        'deleted' => 'Produit supprimé.',
        'images_added' => 'Images ajoutées.',
        'image_deleted' => 'Image supprimée.',
        'bulk_done' => '{0} Aucun produit modifié.|{1} :count produit mis à jour.|[2,*] :count produits mis à jour.',
    ],
    'categories' => [
        'created' => 'Catégorie créée.',
        'updated' => 'Catégorie enregistrée.',
        'deleted' => 'Catégorie supprimée.',
        'not_empty' => 'Impossible de supprimer une catégorie qui contient des sous-catégories ou des produits.',
    ],
    'brands' => [
        'created' => 'Marque créée.',
        'updated' => 'Marque enregistrée.',
        'deleted' => 'Marque supprimée.',
    ],
    'orders' => [
        'status_updated' => 'Statut de la commande mis à jour.',
        'payment_validated' => 'Paiement validé : la commande est payée.',
        'payment_rejected' => 'Justificatif refusé : le client peut en envoyer un autre.',
        'proof_added' => 'Justificatif ajouté à la commande.',
    ],
    'quotes' => [
        'replied' => 'Réponse envoyée au client.',
        'closed' => 'Demande de devis clôturée.',
    ],
    'services' => ['saved' => 'Service enregistré.', 'deleted' => 'Service supprimé.'],
    'service_requests' => ['updated' => 'Demande mise à jour.'],
    'pages' => ['saved' => 'Page enregistrée.'],
    'faq' => ['saved' => 'FAQ enregistrée.', 'deleted' => 'Élément supprimé.'],
    'messages' => ['unread' => 'Message marqué comme non lu.', 'deleted' => 'Message supprimé.'],
    'shipping' => [
        'saved' => 'Zone de livraison enregistrée.',
        'deleted' => 'Zone de livraison supprimée.',
    ],
];
