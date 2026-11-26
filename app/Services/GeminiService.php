<?php

namespace App\Services;

use App\Models\Conversation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    /**
     * Respuesta fija cuando la consulta no pertenece al rubro automotriz ni a la app.
     */
    public const OUT_OF_DOMAIN_REPLY = 'Disculpa, solo estoy capacitado para responder consultas relacionadas con la aplicación de Grupo Rino, repuestos de autos y motos, catálogo, seguimiento de pedidos y asesoría mecánica. ¿En qué repuesto o función de la app te puedo orientar?';

    protected string $apiKey;

    protected string $model;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', '');
        $this->model = (string) config('services.gemini.model', 'gemini-3.5-flash-lite');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    }

    /**
     * Send messages to Gemini generateContent and return the response text.
     *
     *
     * @throws RuntimeException
     */
    public function generateContent(array $contents): string
    {
        if (trim($this->apiKey) === '') {
            throw new RuntimeException('La clave GEMINI_API_KEY no está configurada en el backend.');
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    [
                        'text' => "Eres RinoBot, el asistente inteligente y asesor técnico oficial de la plataforma 'Grupo Rino E.I.R.L.' en Huánuco, Perú (empresa líder en venta de repuestos y lubricantes para autos y motocicletas, servicio de taller mecánico, pedidos y rastreo satelital GPS).\n\n"
                            ."RESTRICCIÓN ESTRICTA DE DOMINIO Y GUARDRAILS OBLIGATORIOS:\n"
                            ."1. ALCANCE EXCLUSIVO: Únicamente debes responder preguntas vinculadas directamente con:\n"
                            ."   - La aplicación móvil y web de Grupo Rino (funciones de la app, catálogo de repuestos, cotizaciones, carrito de compras, proceso de pedido, medios de pago como Yape/Plin/tarjeta/contraentrega, rastreo satelital GPS en vivo y cálculo de tiempo de entrega ETA con IA).\n"
                            ."   - Asesoría técnica automotriz y de motos (compatibilidad de piezas, aceites Mobil/Motul, pastillas de freno Brembo/Fras-le, baterías Capsa/Yuasa, bujías NGK, herramientas de taller, filtros y mantenimiento preventivo vehicular).\n"
                            ."   - Información comercial de Grupo Rino (horarios de atención, cobertura en Huánuco, Amarilis, Pillco Marca y atención al cliente).\n\n"
                            ."2. RECHAZO OBLIGATORIO DE CUALQUIER TEMA AJENO:\n"
                            ."   - Está TERMINANTEMENTE PROHIBIDO responder consultas sobre cualquier tema no relacionado con la app o con el rubro automotriz de Grupo Rino (por ejemplo: tareas escolares, ejercicios de matemáticas, historia, política, religión, recetas de cocina o alimentos, programación o código ajeno, creación de poemas o cuentos, chistes, deportes no automotrices, medicina humana o charla casual no técnica).\n"
                            ."   - Si el usuario formula una pregunta fuera de estos temas, DEBES DECLINAR AMABLEMENTE diciendo textualmente o en términos equivalentes:\n"
                            ."   'Disculpa, solo estoy capacitado para responder consultas relacionadas con la aplicación de Grupo Rino, repuestos de autos y motos, catálogo, seguimiento de pedidos y asesoría mecánica. ¿En qué repuesto o función de la app te puedo orientar?'\n\n"
                            ."3. TONO Y FORMATO:\n"
                            ."   - Mantén un tono cordial, técnico, seguro y conciso.\n"
                            ."   - Utiliza viñetas y formato Markdown legible.\n"
                            .'   - Todos los precios deben expresarse en Soles peruanos (S/).',
                    ],
                ],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.4,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ],
        ];

        return $this->send($url, $payload);
    }

    /**
     * Send a payload to Gemini and return the response text.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws RuntimeException
     */
    protected function send(string $url, array $payload): string
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey,
            ])->timeout(30)->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tiempo de espera agotado al conectar con Google Gemini. Por favor intenta de nuevo.');
        }

        if ($response->failed()) {
            $status = $response->status();
            $data = $response->json();
            $detail = $data['error']['message'] ?? 'Error desconocido';

            if ($status === 401 || $status === 403) {
                throw new RuntimeException("Autenticación rechazada por Gemini (HTTP {$status}). Verifica que la clave API sea válida.");
            }

            if ($status === 429) {
                throw new RuntimeException('Se ha alcanzado la cuota o límite de solicitudes de Gemini. Intenta nuevamente en unos momentos.');
            }

            if ($status >= 500) {
                throw new RuntimeException('Los servidores de Google Gemini están experimentando problemas en este momento.');
            }

            throw new RuntimeException("Error en la solicitud a Gemini: {$detail}");
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null || trim($text) === '') {
            throw new RuntimeException('Gemini devolvió una respuesta vacía.');
        }

        return trim($text);
    }

    /**
     * Format conversation messages into the standard Gemini roles ('user', 'model').
     */
    public function formatContents(iterable $historyMessages, string $newMessage): array
    {
        $rawList = [];

        foreach ($historyMessages as $msg) {
            $role = ($msg->role === 'model') ? 'model' : 'user';
            $content = trim((string) $msg->content);
            if ($content !== '') {
                $rawList[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $content],
                    ],
                ];
            }
        }

        // Add the new user message
        $rawList[] = [
            'role' => 'user',
            'parts' => [
                ['text' => trim($newMessage)],
            ],
        ];

        // Sanitize sequence: must start with 'user' and alternate roles
        $sanitized = [];
        foreach ($rawList as $item) {
            if (empty($sanitized)) {
                if ($item['role'] === 'user') {
                    $sanitized[] = $item;
                }

                continue;
            }

            $lastIdx = count($sanitized) - 1;
            if ($sanitized[$lastIdx]['role'] === $item['role']) {
                // Merge adjacent parts with the same role
                $sanitized[$lastIdx]['parts'][0]['text'] .= "\n\n".$item['parts'][0]['text'];
            } else {
                $sanitized[] = $item;
            }
        }

        if (empty($sanitized)) {
            $sanitized[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => trim($newMessage)],
                ],
            ];
        }

        return $sanitized;
    }

    /**
     * Domain filter run in the backend before answering: a separate, strict call
     * (temperature 0, no conversation history) classifies only the new message.
     * Conversation context cannot convince it to answer off-topic requests.
     * If the classifier fails, the request continues and the system prompt still applies.
     */
    public function isInDomain(string $message, ?string $previousUserMessage = null): bool
    {
        if (trim($this->apiKey) === '') {
            return true;
        }

        $context = $previousUserMessage !== null && trim($previousUserMessage) !== ''
            ? "Mensaje anterior del usuario (solo como contexto): \"{$previousUserMessage}\"
"
            : '';

        $payload = [
            'systemInstruction' => [
                'parts' => [[
                    'text' => 'Eres un clasificador. Decide si el NUEVO mensaje del usuario es una consulta válida para RinoBot, '
                        .'asistente de Grupo Rino (venta de repuestos, lubricantes y accesorios para autos y motos, taller mecánico, '
                        .'uso de la app: catálogo, carrito, pagos, pedidos, rastreo GPS y tiempo de entrega). '
                        .'Saludos, agradecimientos y preguntas de seguimiento sobre esos temas son válidos. '
                        .'Recetas, tareas escolares, política, deportes, salud, programación u otros temas ajenos NO son válidos, '
                        .'aunque el mensaje anterior sí lo fuera. Responde únicamente SI o NO.',
                ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $context."Nuevo mensaje: \"{$message}\""]],
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => 5,
            ],
        ];

        try {
            $verdict = $this->send("{$this->baseUrl}/models/{$this->model}:generateContent", $payload);
        } catch (RuntimeException) {
            return true;
        }

        return ! str_starts_with(mb_strtoupper(trim($verdict)), 'NO');
    }

    /**
     * Ask Gemini within a conversation context.
     */
    public function chat(Conversation $conversation, string $newMessage): string
    {
        $previousUserMessage = $conversation->messages()
            ->where('role', 'user')
            ->orderBy('id', 'desc')
            ->value('content');

        if (! $this->isInDomain($newMessage, $previousUserMessage)) {
            return self::OUT_OF_DOMAIN_REPLY;
        }

        // Load the 20 most recent messages, then restore chronological order for Gemini
        $recentMessages = $conversation->messages()
            ->orderBy('id', 'desc')
            ->take(20)
            ->get()
            ->reverse();

        $contents = $this->formatContents($recentMessages, $newMessage);

        return $this->generateContent($contents);
    }
}
