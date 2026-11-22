<?php

/*
 * French validation messages. Only the rules used by the forms are translated;
 * the others fall back to English (fallback_locale). Add them as new forms need them.
 */
return [

    'accepted' => 'Vous devez accepter :attribute.',
    'confirmed' => 'La confirmation du :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'password' => [
        'letters' => 'Le :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Ce :attribute est apparu dans une fuite de données. Choisissez-en un autre.',
    ],
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'Cette :attribute est déjà utilisée.',
    'uuid' => 'Le champ :attribute est invalide.',

    'attributes' => [
        'current_password' => 'mot de passe actuel',
        'email' => 'adresse email',
        'name' => 'nom complet',
        'organization_id' => 'organisation',
        'password' => 'mot de passe',
        'terms' => 'les conditions d’utilisation',
    ],

];
