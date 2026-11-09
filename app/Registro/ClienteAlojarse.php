<?php

namespace App\Registro;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Envía a alojarse.cl (función importarRegistro de Base44) con el secreto compartido en el encabezado x-cron-secret. */
class ClienteAlojarse
{
    public function __construct(private string $url, private ?string $secreto) {}

    public function estado(): array
    {
        return $this->llamar(['accion' => 'estado']);
    }

    /** Sube una foto WebP y devuelve su URL pública en alojarse. */
    public function subirFoto(string $webp, string $nombre): string
    {
        $r = $this->llamar(['accion' => 'subir_foto', 'nombre' => $nombre, 'base64' => base64_encode($webp)]);
        if (empty($r['url'])) {
            throw new RuntimeException('alojarse no devolvió la URL de la foto');
        }

        return $r['url'];
    }

    /** @return array{resultados: array, resumen: array} */
    public function avisos(array $items): array
    {
        return $this->llamar(['accion' => 'avisos', 'items' => array_values($items)]);
    }

    private function llamar(array $datos): array
    {
        if (! $this->secreto) {
            throw new RuntimeException('Falta ALOJARSE_CRON_SECRET en el .env del servidor');
        }
        $r = Http::withHeaders(['x-cron-secret' => $this->secreto])->acceptJson()->timeout(120)
            ->retry(3, 5000, fn ($e) => ! ($e instanceof \Illuminate\Http\Client\RequestException) || $e->response->serverError(), throw: false)
            ->post($this->url, $datos);
        if (! $r->successful()) {
            throw new RuntimeException('alojarse respondió ' . $r->status() . ': ' . mb_substr((string) ($r->json('error') ?? $r->body()), 0, 200));
        }

        return $r->json() ?? [];
    }
}
