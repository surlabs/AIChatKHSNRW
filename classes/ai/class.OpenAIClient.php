<?php
declare(strict_types=1);

namespace ai;

use OpenAI;
use OpenAI\Client;
use platform\AIChatException;

/**
 * OpenAIClient - Cliente para interactuar con la API de OpenAI Assistants
 * @package ai
 */
class OpenAIClient
{
    private Client $client;
    private string $apiKey;

    /**
     * Constructor del cliente OpenAI
     * @param string $apiKey API key de OpenAI
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->initializeClient();
    }

    /**
     * Procesa un mensaje a través del Asistente de OpenAI
     * Este es el método principal que debe usarse para interactuar con el asistente
     * 
     * @param string $message Mensaje del usuario
     * @param string $threadId ID del hilo de conversación
     * @param string $assistantId ID del asistente de OpenAI
     * @return array Respuesta del asistente con 'role' y 'content'
     * @throws AIChatException
     */
    public function processMessage(string $message, string $threadId, string $assistantId): array 
    {
        try {
            $this->createMessage($threadId, $message);
            $run = $this->createRun($threadId, $assistantId);
            $this->waitForRunCompletion($threadId, $run['id']);
            
            $messages = $this->listThreadMessages($threadId);
            if (empty($messages['data'])) {
                throw new AIChatException("No response received from assistant");
            }

            return [
                'role' => 'assistant',
                'content' => $messages['data'][0]['content'][0]['text']['value']
            ];
        } catch (\Exception $e) {
            throw new AIChatException("Error in OpenAI processing: " . $e->getMessage());
        }
    }

    /**
     * Crea un nuevo hilo de conversación
     * @param array $messages Mensajes iniciales opcionales
     * @return array Datos del hilo creado
     * @throws AIChatException
     */
    public function createThread(array $messages = []): array
    {
        try {
            $parameters = [];
            if (!empty($messages)) {
                $parameters['messages'] = $messages;
            }
            return $this->client->threads()->create($parameters)->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error creating thread: " . $e->getMessage());
        }
    }

    /**
     * Elimina un hilo de conversación
     * @param string $threadId ID del hilo
     * @return array Resultado de la eliminación
     * @throws AIChatException
     */
    public function deleteThread(string $threadId): array
    {
        try {
            return $this->client->threads()->delete($threadId)->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error deleting thread: " . $e->getMessage());
        }
    }

    private function initializeClient(): void
    {
        $this->client = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();
    }

    private function createMessage(string $threadId, string $content, string $role = 'user'): array
    {
        try {
            return $this->client->threads()->messages()->create(
                threadId: $threadId,
                parameters: [
                    'role' => $role,
                    'content' => $content
                ]
            )->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error creating message: " . $e->getMessage());
        }
    }

    private function createRun(string $threadId, string $assistantId): array
    {
        try {
            return $this->client->threads()->runs()->create(
                threadId: $threadId,
                parameters: [
                    'assistant_id' => $assistantId
                ]
            )->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error creating run: " . $e->getMessage());
        }
    }

    private function retrieveRun(string $threadId, string $runId): array
    {
        try {
            return $this->client->threads()->runs()->retrieve(
                threadId: $threadId,
                runId: $runId
            )->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error retrieving run: " . $e->getMessage());
        }
    }

    /**
     * Obtiene todos los mensajes de un hilo de conversación
     * @param string $threadId ID del hilo
     * @param int $limit Número máximo de mensajes a recuperar (0 = sin límite)
     * @param string $order Orden de los mensajes ('asc' o 'desc')
     * @return array Lista de mensajes del hilo
     * @throws AIChatException
     */
    public function getThreadMessages(string $threadId, int $limit = 0, string $order = 'asc'): array 
    {
        try {
            $parameters = [
                'order' => $order
            ];
            
            if ($limit > 0) {
                $parameters['limit'] = $limit;
            }

            $messages = $this->client->threads()->messages()->list(
                threadId: $threadId,
                parameters: $parameters
            )->toArray();

            // Transformar los mensajes al formato que espera el frontend
            return array_map(function($message) {
                return [
                    'role' => $message['role'],
                    'content' => $message['content'][0]['text']['value'],
                    'created_at' => $message['created_at']
                ];
            }, $messages['data']);

        } catch (\Exception $e) {
            throw new AIChatException("Error getting thread messages: " . $e->getMessage());
        }
    }

    private function listThreadMessages(string $threadId): array
    {
        try {
            return $this->client->threads()->messages()->list(
                threadId: $threadId,
                parameters: [
                    'limit' => 1,
                    'order' => 'desc'
                ]
            )->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error listing thread messages: " . $e->getMessage());
        }
    }

    private function waitForRunCompletion(
        string $threadId,
        string $runId,
        int $maxAttempts = 60,
        int $delaySeconds = 1
    ): array {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $run = $this->retrieveRun($threadId, $runId);

            switch ($run['status']) {
                case 'completed':
                    return $run;
                case 'queued':
                case 'in_progress':
                    break;
                case 'failed':
                    throw new AIChatException("Run failed: " . ($run['last_error']['message'] ?? 'Unknown error'));
                case 'cancelled':
                    throw new AIChatException("Run was cancelled");
                case 'expired':
                    throw new AIChatException("Run expired");
                case 'requires_action':
                    throw new AIChatException("Run requires action: function calling not implemented");
            }

            $attempts++;
            if ($attempts < $maxAttempts) {
                sleep($delaySeconds);
            }
        }

        throw new AIChatException("Run timed out after {$maxAttempts} attempts");
    }
}
