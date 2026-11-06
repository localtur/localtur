<?php

// Países de localtur. Cada uno tiene su carpeta en la URL (localtur.com/cl, /pe, /co), sus colores,
// su moneda y la forma en que llama a sus divisiones territoriales.
//
// Las tablas son las mismas para todos: regiones → comunas → localidades.
//   Chile:    Región → Comuna → Localidad
//   Perú:     Departamento → Distrito → Localidad   (la provincia queda como dato de la localidad)
//   Colombia: Departamento → Municipio → Localidad / vereda
//   México:   Estado → Municipio → Localidad
//
// Medios de pago: solo Mercado Pago y PayPal en todos los países (por ahora).

return [
    'cl' => [
        'nombre' => 'Chile',
        'activo' => true,                 // ya tiene avisos
        'locale' => 'es_CL',
        'hreflang' => 'es-CL',
        'moneda' => 'CLP',
        'prefijo' => '+56',
        'id_fiscal' => 'RUT',
        'division' => ['region' => 'Región', 'comuna' => 'Comuna', 'localidad' => 'Localidad'],
        'colores' => ['principal' => '#0039a6', 'secundario' => '#d52b1e', 'suave' => '#e8eefa', 'tinta_hero' => '#ffffff', 'hero' => '#0039a6', 'hero_2' => '#002a7a',
            'franja' => 'linear-gradient(90deg,#fff 0 50%,#d52b1e 50%)'],
        'bandera' => 'cl',
        'eslogan' => 'Alojamiento y vida local en Chile, directo y sin comisiones',
        // En localtur, Chile es solo la Región Metropolitana (el resto está en alojarse.cl)
        'destinos' => ['Santiago', 'Cajón del Maipo', 'Farellones', 'Pirque', 'Isla de Maipo', 'Talagante'],
        'pagos' => ['Mercado Pago', 'PayPal'],
    ],
    'pe' => [
        'nombre' => 'Perú',
        'activo' => false,
        'locale' => 'es_PE',
        'hreflang' => 'es-PE',
        'moneda' => 'PEN',
        'prefijo' => '+51',
        'id_fiscal' => 'RUC',
        'division' => ['region' => 'Departamento', 'comuna' => 'Distrito', 'localidad' => 'Localidad'],
        'colores' => ['principal' => '#c8102e', 'secundario' => '#7a0a1c', 'suave' => '#fbeaec', 'tinta_hero' => '#ffffff', 'hero' => '#d91023', 'hero_2' => '#a50c1b',
            'franja' => 'linear-gradient(90deg,#d91023 0 33.3%,#fff 33.3% 66.6%,#d91023 66.6%)'],
        'bandera' => 'pe',
        'eslogan' => 'Alojamiento y vida local en Perú, directo y sin comisiones',
        'destinos' => ['Cusco', 'Valle Sagrado', 'Arequipa', 'Lima', 'Paracas', 'Máncora'],
        'pagos' => ['Mercado Pago', 'PayPal'],
    ],
    'co' => [
        'nombre' => 'Colombia',
        'activo' => false,
        'locale' => 'es_CO',
        'hreflang' => 'es-CO',
        'moneda' => 'COP',
        'prefijo' => '+57',
        'id_fiscal' => 'NIT',
        'division' => ['region' => 'Departamento', 'comuna' => 'Municipio', 'localidad' => 'Localidad'],
        'colores' => ['principal' => '#003893', 'secundario' => '#ce1126', 'suave' => '#fff6cc', 'tinta_hero' => '#1f2a28', 'hero' => '#fcd116', 'hero_2' => '#f5c400',
            'franja' => 'linear-gradient(180deg,#003893 0 50%,#ce1126 50%)'],
        'bandera' => 'co',
        'eslogan' => 'Alojamiento y vida local en Colombia, directo y sin comisiones',
        'destinos' => ['Cartagena', 'Medellín', 'Eje Cafetero', 'Santa Marta', 'Bogotá', 'San Andrés'],
        'pagos' => ['Mercado Pago', 'PayPal'],
    ],
    'mx' => [
        'nombre' => 'México',
        'activo' => false,
        'locale' => 'es_MX',
        'hreflang' => 'es-MX',
        'moneda' => 'MXN',
        'prefijo' => '+52',
        'id_fiscal' => 'RFC',
        'division' => ['region' => 'Estado', 'comuna' => 'Municipio', 'localidad' => 'Localidad'],
        'colores' => ['principal' => '#006847', 'secundario' => '#ce1126', 'suave' => '#e6f2ec', 'tinta_hero' => '#ffffff', 'hero' => '#006847', 'hero_2' => '#004d35',
            'franja' => 'linear-gradient(90deg,#006847 0 33.3%,#fff 33.3% 66.6%,#ce1126 66.6%)'],
        'bandera' => 'mx',
        'eslogan' => 'Alojamiento y vida local en México, directo y sin comisiones',
        'destinos' => ['Ciudad de México', 'Oaxaca', 'Riviera Maya', 'San Miguel de Allende', 'Mérida', 'Puerto Vallarta'],
        'pagos' => ['Mercado Pago', 'PayPal'],
    ],
];
