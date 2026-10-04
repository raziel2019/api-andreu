<?php

return [
    'required' => 'El camp :attribute és obligatori.',
    'present' => 'Cal incloure el camp :attribute.',
    'email' => 'El camp :attribute ha de ser un correu electrònic vàlid.',
    'url' => 'El camp :attribute ha de ser un URL HTTP/HTTPS vàlid.',
    'array' => 'El camp :attribute ha de tenir una estructura vàlida.',
    'string' => 'El camp :attribute ha de ser un text.',
    'boolean' => 'El camp :attribute ha de ser sí o no.',
    'integer' => 'El camp :attribute ha de ser un número enter.',
    'exists' => 'L’element seleccionat a :attribute no és vàlid.',
    'distinct' => 'El camp :attribute està duplicat.',
    'regex' => 'El format del camp :attribute no és vàlid.',
    'image' => 'El camp :attribute ha de ser una imatge.',
    'mimes' => 'La imatge :attribute ha de ser de tipus :values.',
    'extensions' => 'L’extensió de :attribute ha de ser :values.',
    'dimensions' => 'Les dimensions de :attribute no són vàlides (màxim 6000 × 6000).',
    'max' => [
        'numeric' => 'El camp :attribute no pot superar :max.',
        'file' => 'El fitxer :attribute no pot superar :max KB.',
        'string' => 'El camp :attribute no pot superar :max caràcters.',
        'array' => 'El camp :attribute no pot tenir més de :max elements.',
    ],
    'min' => [
        'numeric' => 'El camp :attribute ha de ser com a mínim :min.',
        'string' => 'El camp :attribute ha de tenir com a mínim :min caràcters.',
    ],
    'attributes' => [
        'email' => 'correu electrònic', 'password' => 'contrasenya',
        'hero_image' => 'imatge principal', 'portfolio_image' => 'imatge de portafoli',
        'contact_email' => 'correu de contacte', 'linkedin_url' => 'URL de LinkedIn', 'instagram_url' => 'URL d’Instagram',
        'service_summaries.*.sort_order' => 'ordre del resum',
    ],
];
