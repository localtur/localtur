<?php

// Importador nocturno del registro público de prestadores de servicios turísticos (Chile) hacia alojarse.cl.
// Corre en Hostinger: lee las fichas, optimiza las fotos que el propio negocio publicó al inscribirse y
// envía todo a alojarse por un endpoint protegido. Ver App\Console\Commands\ImportarRegistro.
return [
    'base' => 'https://serviciosturisticos.sernatur.cl',

    // tipo_servicio del registro → se importan todos menos transporte aéreo (6) y ferroviario (8)
    'tipos' => [
        '1' => 'Alojamiento turístico', '2' => 'Restaurantes y similares', '18' => 'Tour operador',
        '13' => 'Turismo aventura', '16' => 'Guías de turismo', '3' => 'Agencia de viajes',
        '5' => 'Transporte marítimo', '10' => 'Arriendo de vehículos', '12' => 'Servicios culturales',
        '14' => 'Servicios de esparcimiento', '15' => 'Producción artesanal', '17' => 'Servicios deportivos',
        '19' => 'Transporte terrestre', '22' => 'Souvenirs',
    ],
    'excluidos' => ['6', '8'],

    'agente' => 'AlojarseImportador/1.0 (+https://alojarse.cl)',
    'pausa_ms' => (int) env('REGISTRO_PAUSA_MS', 1500),      // entre peticiones al registro (ser amables)
    'fichas_por_noche' => (int) env('REGISTRO_FICHAS_POR_NOCHE', 1500),
    'paginas_por_noche' => (int) env('REGISTRO_PAGINAS_POR_NOCHE', 400),
    'minutos_max' => (int) env('REGISTRO_MINUTOS_MAX', 240),
    'relistar_dias' => 7,     // cada cuánto se recorre de nuevo el listado de un tipo
    'revisar_dias' => 30,     // cada cuánto se vuelve a leer una ficha ya enviada

    'fotos' => [
        'max' => 10,
        'lado_max' => 1280,
        'lado_min' => 240,        // más chica que esto no sirve (íconos, logos)
        'calidad' => 74,
        'peso_max' => 350 * 1024, // si pesa más, se vuelve a comprimir
        'parecidas' => 5,         // distancia máxima entre huellas para considerarlas la misma foto
        'generica_usos' => 3,     // la misma imagen en 3 fichas distintas = imagen genérica, se descarta
    ],

    'geocodificar' => env('REGISTRO_GEOCODIFICAR', true),
    'nominatim' => 'https://nominatim.openstreetmap.org/search',

    'alojarse' => [
        'url' => env('ALOJARSE_IMPORTAR_URL', 'https://alojarse.cl/functions/importarRegistro'),
        'secreto' => env('ALOJARSE_CRON_SECRET'),
        'lote' => 25,
    ],
];
