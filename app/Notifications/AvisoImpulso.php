<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso del módulo Impulso MyPerú: campana (database), aviso emergente en pantalla (toast) y correo.
 * Los canales de cada evento siguen la tabla de la sección 5 de docs/PROCESO_IMPULSO_MYPERU.md.
 */
class AvisoImpulso extends Notification
{
    use Queueable;

    /**
     * @param array $d evento, titulo, mensaje, url, icono, color (ok|warn|danger|info), detalle ([[etiqueta, valor]]),
     *                 toast (bool), correo (bool), campana (bool)
     */
    public function __construct(public array $d) {}

    public function via(object $notifiable): array
    {
        $canales = [];
        if (($this->d['campana'] ?? true) && method_exists($notifiable, 'getKey')) $canales[] = 'database';
        if ($this->d['correo'] ?? false) $canales[] = 'mail';
        return $canales;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'evento'  => $this->d['evento'] ?? 'impulso',
            'titulo'  => $this->d['titulo'],
            'mensaje' => $this->d['mensaje'],
            'url'     => $this->d['url'] ?? null,
            'icono'   => $this->d['icono'] ?? 'bi-bank',
            'color'   => $this->d['color'] ?? 'info',
            'toast'   => (bool) ($this->d['toast'] ?? false),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from('rct@cajatrujillo.pe', 'RCT · Caja Trujillo')
            ->subject('[RCT · Impulso MyPerú] '.$this->d['titulo'])
            ->view('emails.aviso', [
                'titulo'  => $this->d['titulo'],
                'mensaje' => $this->d['mensaje'],
                'detalle' => $this->d['detalle'] ?? [],
                'url'     => $this->d['url'] ?? null,
                'nombre'  => $notifiable->name ?? null,
            ]);
    }
}
