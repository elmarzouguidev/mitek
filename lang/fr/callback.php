<?php

return [
    'title' => 'Être rappelé',
    'description' => 'Laissez votre numéro : un conseiller Mitek vous rappelle pour répondre à votre demande.',
    'intro' => 'Laissez-nous votre numéro et le créneau qui vous convient : nous vous rappelons.',
    'close' => 'Fermer',
    'sent' => 'Merci, votre demande de rappel est bien enregistrée. Nous vous rappelons au numéro indiqué.',
    'error' => 'Votre demande n\'a pas pu être envoyée. Réessayez, ou écrivez-nous via la page contact.',
    'throttled' => 'Trop de tentatives. Patientez une minute avant de réessayer.',
    'form' => [
        'name' => 'Nom',
        'phone' => 'Téléphone',
        'phone_hint' => 'Ex. 0600 00 00 00 ou +212 600 00 00 00',
        'subject' => 'Sujet',
        'slot' => 'Meilleur créneau',
        'note' => 'Précisions (facultatif)',
        'submit' => 'Être rappelé',
        'sending' => 'Envoi…',
        'privacy' => 'Vos coordonnées servent uniquement à vous rappeler.',
        'privacy_link' => 'Politique de confidentialité',
    ],
    'subjects' => [
        'order' => 'Une commande',
        'quote' => 'Un devis',
        'product' => 'Un produit',
        'delivery' => 'La livraison',
        'service' => 'Un service informatique',
        'other' => 'Autre sujet',
    ],
    'slots' => [
        'morning' => 'Matin',
        'afternoon' => 'Après-midi',
        'anytime' => 'Peu importe',
    ],
    'statuses' => [
        'new' => 'À rappeler',
        'called' => 'Rappelé',
        'closed' => 'Clôturé',
    ],
];
