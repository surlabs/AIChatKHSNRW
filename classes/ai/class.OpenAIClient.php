<?php
declare(strict_types=1);

namespace ai;

use OpenAI;
use OpenAI\Client;
use platform\AIChatException;

class OpenAIClient
{
    private Client $client;
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->initializeClient();
    }

    private function initializeClient(): void
    {
        $this->client = OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();
    }

    /**
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
     * @throws AIChatException
     */
    public function retrieveThread(string $threadId): array
    {
        try {
            return $this->client->threads()->retrieve($threadId)->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error retrieving thread: " . $e->getMessage());
        }
    }

    /**
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

    /**
     * @throws AIChatException
     */
    public function createMessage(string $threadId, string $content, string $role = 'user'): array
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

    /**
     * @throws AIChatException
     */
    public function retrieveMessage(string $threadId, string $messageId): array
    {
        try {
            return $this->client->threads()->messages()->retrieve(
                threadId: $threadId,
                messageId: $messageId
            )->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error retrieving message: " . $e->getMessage());
        }
    }

    /**
     * @throws AIChatException
     */
    public function createRun(string $threadId, string $assistantId): array
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

    /**
     * @throws AIChatException
     */
    public function retrieveRun(string $threadId, string $runId): array
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
     * @throws AIChatException
     */
    public function retrieveAssistant(string $assistantId): array
    {
        try {
            return $this->client->assistants()->retrieve($assistantId)->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error retrieving assistant: " . $e->getMessage());
        }
    }

    /**
     * @throws AIChatException
     */
    public function createAndRun(string $assistantId, array $messages): array
    {
        try {
            return $this->client->threads()->createAndRun([
                'assistant_id' => $assistantId,
                'thread' => [
                    'messages' => $messages
                ]
            ])->toArray();
        } catch (\Exception $e) {
            throw new AIChatException("Error in create and run: " . $e->getMessage());
        }
    }

    public function listThreadMessages(string $threadId): array
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

    /**
     * Waits for a run to complete, checking its status periodically
     *
     * @param string $threadId The ID of the thread
     * @param string $runId The ID of the run
     * @param int $maxAttempts Maximum number of polling attempts (default: 60)
     * @param int $delaySeconds Delay between polling attempts in seconds (default: 1)
     * @return array The final run status
     * @throws AIChatException If the run fails or times out
     */
    public function waitForRunCompletion(
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
