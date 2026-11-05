<?php

// Marca por dominio: el mismo código sirve a localtur.com y a alojarse.cl.
// La marca activa se elige en App\Support\Marca según el host de la petición.

return [
    'por_defecto' => env('MARCA', 'localtur'),

    // Mientras sea prototipo, ninguna página se indexa (cabecera X-Robots-Tag y robots.txt).
    'indexar' => (bool) env('MARCA_INDEXAR', false),

    'marcas' => [
        'localtur' => [
            'nombre' => 'localtur',
            'dominio' => 'localtur.com',
            'hosts' => ['localtur.com', 'www.localtur.com'],
            'eslogan' => 'Alojamiento y vida local, directo y sin comisiones',
            'pais' => 'CL',
            'moneda' => 'CLP',
            'locale' => 'es_CL',
        ],
        'alojarse' => [
            'nombre' => 'alojarse.cl',
            'dominio' => 'alojarse.cl',
            'hosts' => ['alojarse.cl', 'www.alojarse.cl'],
            'eslogan' => 'Encuentra dónde alojarte y qué hacer, directo y sin comisiones',
            'pais' => 'CL',
            'moneda' => 'CLP',
            'locale' => 'es_CL',
        ],
    ],

    // Categorías: clave interna => [slug en la URL, nombre, tipo schema.org]
    'categorias' => [
        'alojar' => ['slug' => 'alojamiento', 'nombre' => 'Alojar', 'schema' => 'LodgingBusiness'],
        'comer' => ['slug' => 'comer', 'nombre' => 'Comer', 'schema' => 'Restaurant'],
        'hacer' => ['slug' => 'que-hacer', 'nombre' => 'Qué hacer', 'schema' => 'TouristAttraction'],
        'comercio' => ['slug' => 'comercio', 'nombre' => 'Comercio', 'schema' => 'Store'],
        'servicios' => ['slug' => 'servicios', 'nombre' => 'Servicios', 'schema' => 'LocalBusiness'],
    ],
];
