<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'present' => 'Hay que incluir el campo :attribute.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'url' => 'El campo :attribute debe ser un URL HTTP/HTTPS válido.',
    'array' => 'El campo :attribute debe tener una estructura válida.',
    'string' => 'El campo :attribute debe ser un texto.',
    'boolean' => 'El campo :attribute debe ser sí o no.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'distinct' => 'El campo :attribute está duplicado.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'mimes' => 'La imagen :attribute debe ser de tipo :values.',
    'extensions' => 'La extensión de :attribute debe ser :values.',
    'dimensions' => 'Las dimensiones de :attribute no son válidas (máximo 6000 × 6000).',
    'max' => [
        'numeric' => 'El campo :attribute no puede superar :max.',
        'file' => 'El archivo :attribute no puede superar :max KB.',
        'string' => 'El campo :attribute no puede superar :max caracteres.',
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser como mínimo :min.',
        'string' => 'El campo :attribute debe tener como mínimo :min caracteres.',
    ],
    'attributes' => [
        'email' => 'correo electrónico', 'password' => 'contraseña',
        'hero_image' => 'imagen principal', 'portfolio_image' => 'imagen de portafolio',
        'contact_email' => 'correo de contacto', 'linkedin_url' => 'URL de LinkedIn', 'instagram_url' => 'URL de Instagram',
        'service_summaries.*.sort_order' => 'orden del resumen',
    ],
];
