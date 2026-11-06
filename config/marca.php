<?php

// Marca por dominio: el mismo código sirve a localtur.com y a alojarse.cl.
// La marca activa se elige en App\Support\Marca según el host de la petición.

return [
    'por_defecto' => env('MARCA', 'localtur'),

    // Mientras sea prototipo, ninguna página se indexa (cabecera X-Robots-Tag y robots.txt).
    'indexar' => (bool) env('MARCA_INDEXAR', false),

    // hosts: dominios que sirven la marca. alias: redirigen 301 al dominio principal.
    // dominio_prueba: donde se ve la marca mientras es prototipo (enlaces cruzados y "ver" del panel).
    // regiones: qué regiones de cada país muestra la marca (solo / excluir, por slug de región).
    // Chile se reparte: la Región Metropolitana en localtur.com/cl, todo el resto en alojarse.cl.
    'marcas' => [
        'localtur' => [
            'nombre' => 'LocalTur.com',
            'dominio' => 'localtur.com',
            'dominio_prueba' => 'staging.localtur.com',
            'hosts' => ['localtur.com', 'staging.localtur.com'],
            'alias' => ['www.localtur.com'],
            'eslogan' => 'Alojamiento y vida local en América Latina, directo y sin comisiones',
            'regiones' => ['cl' => ['solo' => ['metropolitana-de-santiago']]],
            'locale' => 'es_CL',
        ],
        'alojarse' => [
            'nombre' => 'alojarse.cl',
            'dominio' => 'alojarse.cl',
            'dominio_prueba' => 'prueba-alojarse.localtur.com',
            'pais_fijo' => 'cl',   // marca de un solo país: URLs sin carpeta de país
            'hosts' => ['alojarse.cl', 'prueba-alojarse.localtur.com'],
            'alias' => ['www.alojarse.cl'],
            'eslogan' => 'Encuentra dónde alojarte y qué hacer, directo y sin comisiones',
            'regiones' => ['cl' => ['excluir' => ['metropolitana-de-santiago']]],
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
