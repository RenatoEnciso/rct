<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\{PropuestaFud, ClienteCartera, User};

class AsistenteController extends Controller
{
    public function chat(Request $request)
{
    $request->validate(['mensaje' => 'required|string|max:500']);

    $user     = $request->user();
    $contexto = $this->construirContexto($user);

    $prompt = <<<PROMPT
Eres el asistente virtual del sistema RCT Recuperaciones de Caja Trujillo.
Respondes de forma concisa, profesional y en español.
Solo respondes preguntas relacionadas al sistema de recuperaciones y cobranzas.

CONTEXTO ACTUAL DEL USUARIO:
{$contexto}

PREGUNTA DEL USUARIO:
{$request->mensaje}
PROMPT;

    $apiKey = env('GEMINI_API_KEY');

    $response = Http::timeout(15)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature'     => 0.3,
                    'maxOutputTokens' => 1024,
                ],
            ]
        );

    // Log para debug
    \Log::info('Gemini status: ' . $response->status());
    \Log::info('Gemini body: '   . $response->body());

    if ($response->failed()) {
        return response()->json([
            'respuesta' => 'Error ' . $response->status() . ': ' . $response->body()
        ], 500);
    }

    $texto = $response->json('candidates.0.content.parts.0.text')
             ?? 'No pude generar una respuesta.';

    return response()->json(['respuesta' => trim($texto)]);
}
    private function construirContexto(User $user): string
    {
        $rol     = $user->rol->nombre;
        $esAprob = $user->rol->grupo === 'aprobador';

        if ($esAprob) {
            $pendientes = PropuestaFud::where('aprobador_id', $user->id)
                ->where('estado_fud', 'En revisión')->count();
            $aprobados  = PropuestaFud::where('aprobado_por', $user->id)
                ->where('estado_fud', 'Aprobado')
                ->whereDate('fecha_decision', today())->count();
            $observados = PropuestaFud::where('aprobador_id', $user->id)
                ->where('estado_fud', 'Observado')->count();

            return <<<CTX
Usuario: {$user->name}
Rol: {$rol}
FUDs pendientes de revisión: {$pendientes}
FUDs aprobados hoy: {$aprobados}
FUDs observados (devueltos): {$observados}
CTX;
        }

        // Ejecutivo
        $misEnviados  = PropuestaFud::where('enviado_por', $user->id)->count();
        $enRevision   = PropuestaFud::where('enviado_por', $user->id)
            ->where('estado_fud', 'En revisión')->count();
        $observados   = PropuestaFud::where('enviado_por', $user->id)
            ->where('estado_fud', 'Observado')->count();
        $aprobados    = PropuestaFud::where('enviado_por', $user->id)
            ->where('estado_fud', 'Aprobado')->count();
        $clientesDisp = ClienteCartera::disponibles()
            ->whereHas('tienda', fn($q) => $q->where('id', $user->tienda_id))
            ->count();

        return <<<CTX
Usuario: {$user->name}
Rol: {$rol}
Tienda asignada: {$user->tienda->nombre}
Total FUDs enviados: {$misEnviados}
FUDs en revisión: {$enRevision}
FUDs observados (requieren corrección): {$observados}
FUDs aprobados: {$aprobados}
Clientes disponibles en cartera de campaña (tu tienda): {$clientesDisp}
CTX;
    }
    
}