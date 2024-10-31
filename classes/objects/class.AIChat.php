<?php
declare(strict_types=1);
/**
 *  This file is part of the AI Chat Repository Object plugin for ILIAS, which allows your platform's users
 *  To connect with an external LLM service
 *  This plugin is created and maintained by SURLABS.
 *
 *  The AI Chat Repository Object plugin for ILIAS is open-source and licensed under GPL-3.0.
 *  For license details, visit https://www.gnu.org/licenses/gpl-3.0.en.html.
 *
 *  To report bugs or participate in discussions, visit the Mantis system and filter by
 *  the category "AI Chat" at https://mantis.ilias.de.
 *
 *  More information and source code are available at:
 *  https://github.com/surlabs/AIChat
 *
 *  If you need support, please contact the maintainer of this software at:
 *  info@surlabs.es
 *
 */

namespace objects;

use ai\LLM;
use ai\CustomAI;
use ai\OpenAI;
use ai\OpenAIClient;
use platform\AIChatConfig;
use platform\AIChatDatabase;
use platform\AIChatException;

/**
 * Class AIChat
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 */
class AIChat
{
    private const ASSISTANT_ID = "asst_abc123"; // Valor hardcodeado del assistant_id

    private int $id = 0;
    private bool $online = false;
    private string $provider = "";
    private string $model = "";
    private string $api_key = "";
    private ?bool $streaming = null;
    private string $url = "";
    private string $prompt = "";
    private int $char_limit = 0;
    private int $max_memory_messages = 0;
    private string $disclaimer = "";
    private LLM $llm;
    private ?OpenAIClient $openai_client = null;

    public function __construct(?int $id = null)
    {
        if ($id !== null && $id > 0) {
            $this->id = $id;

            $this->loadFromDB();
        }

        $this->loadLLM();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function isOnline(): bool
    {
        return $this->online;
    }

    public function setOnline(bool $online): void
    {
        $this->online = $online;
    }

    /**
     * @throws AIChatException
     */
    public function getProvider(bool $strict = false): string
    {
        if ((empty($this->provider) || $this->provider == "default") && !$strict) {
            return AIChatConfig::get("llm_provider") != "" ? AIChatConfig::get("llm_provider") : "openai";
        }

        return $this->provider;
    }

    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @throws AIChatException
     */
    public function getModel(bool $strict = false): string
    {
        if ((empty($this->model) || $this->getProvider(true) == "default") && !$strict) {
            return AIChatConfig::get("llm_model") ?? "openai";
        }

        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * @throws AIChatException
     */
    public function getApiKey(bool $strict = false): string
    {
        if ((empty($this->api_key) || $this->getProvider(true) == "default") && !$strict) {
            return AIChatConfig::get("global_api_key");
        }

        return $this->api_key;
    }

    public function setApiKey(string $api_key): void
    {
        $this->api_key = $api_key;
    }

    /**
     * @throws AIChatException
     */
    public function isStreaming(bool $strict = false): bool
    {
        if (!$strict && $this->getProvider() == "openai") {
            return $this->streaming ?? AIChatConfig::get("streaming") == "1";
        }

        return $this->streaming ?? false;
    }

    public function setStreaming(bool $streaming): void
    {
        $this->streaming = $streaming;
    }

    /**
     * @throws AIChatException
     */
    public function getUrl(bool $strict = false): string
    {
        if ((empty($this->url) || $this->getProvider(true) == "default") && !$strict) {
            return AIChatConfig::get("llm_url");
        }

        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @throws AIChatException
     */
    public function getPrompt(bool $strict = false): string
    {
        if (empty($this->prompt) && !$strict) {
            return AIChatConfig::get("prompt_selection");
        }

        return $this->prompt;
    }

    public function setPrompt(string $prompt): void
    {
        $this->prompt = $prompt;
    }

    /**
     * @throws AIChatException
     */
    public function getCharLimit(bool $strict = false): int
    {
        if ($this->char_limit == 0 && !$strict) {
            return AIChatConfig::get("characters_limit") != "" ? (int) AIChatConfig::get("characters_limit") : 100;
        }

        return $this->char_limit;
    }

    public function setCharLimit(?int $char_limit = null): void
    {
        if ($char_limit == null) {
            $char_limit = 0;
        }

        $this->char_limit = $char_limit;
    }

    /**
     * @throws AIChatException
     */
    public function getMaxMemoryMessages(bool $strict = false): int
    {
        if ($this->max_memory_messages == 0 && !$strict) {
            return AIChatConfig::get("n_memory_messages") != "" ? (int) AIChatConfig::get("n_memory_messages") : 5;
        }

        return $this->max_memory_messages;
    }

    public function setMaxMemoryMessages(?int $max_memory_messages = null): void
    {
        if ($max_memory_messages == null) {
            $max_memory_messages = 0;
        }

        $this->max_memory_messages = $max_memory_messages;
    }

    /**
     * @throws AIChatException
     */
    public function getDisclaimer(bool $strict = false): string
    {
        if (empty($this->disclaimer) && !$strict) {
            return AIChatConfig::get("disclaimer_text");
        }

        return $this->disclaimer;
    }

    public function setDisclaimer(string $disclaimer): void
    {
        $this->disclaimer = $disclaimer;
    }

    public function getAssistantId(): string 
    {
        return self::ASSISTANT_ID;
    }

    /**
     * @throws AIChatException
     */
    public function getThreadsForApi(?int $user_id = null): array
    {
        $database = new AIChatDatabase();

        $where = [
            "obj_id" => $this->getId(),
        ];

        if (isset($user_id) && $user_id > 0) {
            $where["user_id"] = $user_id;
        }

        return $database->select("xaic_threads", $where, null, "ORDER BY created_at DESC");
    }

    /**
     * @throws AIChatException
     */
    public function loadFromDB(): void
    {
        $database = new AIChatDatabase();

        $result = $database->select("xaic_objects", ["id" => $this->getId()]);

        if (isset($result[0])) {
            $this->setOnline((bool) $result[0]["online"]);
            $this->setProvider((string) $result[0]["provider"]);
            $this->setModel((string) $result[0]["model"]);
            $this->setApiKey((string) $result[0]["api_key"]);
            $this->setStreaming((bool) $result[0]["streaming"]);
            $this->setUrl((string) $result[0]["url"]);
            $this->setPrompt((string) $result[0]["prompt"]);
            $this->setCharLimit((int) $result[0]["char_limit"]);
            $this->setMaxMemoryMessages((int) $result[0]["max_memory_messages"]);
            $this->setDisclaimer((string) $result[0]["disclaimer"]);
        }
    }

    /**
     * @throws AIChatException
     */
    public function save(): void
    {
        if (!isset($this->id) || $this->id == 0) {
            throw new AIChatException("AIChat::save() - AIChat ID is 0");
        }

        $database = new AIChatDatabase();

        $database->insertOnDuplicatedKey("xaic_objects", array(
            "id" => $this->id,
            "online" => (int) $this->online,
            "provider" => $this->provider,
            "model" => $this->model,
            "api_key" => $this->api_key,
            "streaming" => (int) $this->streaming,
            "url" => $this->url,
            "prompt" => $this->prompt,
            "char_limit" => $this->char_limit,
            "max_memory_messages" => $this->max_memory_messages,
            "disclaimer" => $this->disclaimer
        ));
    }

    /**
     * @throws AIChatException
     */
    public function delete(): void
    {
        $database = new AIChatDatabase();

        $database->delete("xaic_objects", ["id" => $this->id]);

        $threads = $database->select("xaic_threads", ["obj_id" => $this->id]);

        foreach ($threads as $thread) {
            $thread_obj = new Thread($thread["id"]);
            $thread_obj->delete();
        }
    }

    /**
     * @throws AIChatException
     */
    public function processMessage(string $message, ?Thread $thread = null): array
    {
        if ($this->getProvider() !== "openai") {
            throw new AIChatException("Only OpenAI provider is supported for now");
        }

        // Initialize OpenAI client if not already done
        if ($this->openai_client === null) {
            $this->openai_client = new OpenAIClient($this->getApiKey());
        }

        try {
            // Si no hay thread, crear uno nuevo tanto local como en OpenAI
            if ($thread === null) {
                $thread = new Thread();
                $thread->setObjId($this->getId());
                
                // Obtener el user_id actual
                global $DIC;
                $thread->setUserId($DIC->user()->getId());
            }

            // Asegurarse de que tenemos un thread en OpenAI
            if (empty($thread->getThreadId())) {
                $result = $this->openai_client->createThread();
                $thread->setThreadId($result['id']);
            }

            // Procesar el mensaje y obtener la respuesta
            $response = $this->openai_client->processMessage(
                $message,
                $thread->getThreadId(),
                $this->getAssistantId()
            );

            // Si es un nuevo thread, establecer el título y guardar
            if ($thread->getId() === 0) {
                $thread->setTitleFromMessage($message);
                $thread->save();
            }

            return $response;

        } catch (\Exception $e) {
            throw new AIChatException("Error processing message: " . $e->getMessage());
        }
    }

    /**
     * @throws AIChatException
     */
    private function loadLLM()
    {
        $provider = $this->getProvider();
        $model = $this->getModel();

        if (!empty($provider) && !empty($model)) {
            switch ($provider) {
                case "openai":
                    $this->llm = new OpenAI($model);
                    $this->llm->setApiKey($this->getApiKey());
                    $this->llm->setMaxMemoryMessages($this->getMaxMemoryMessages());
                    $this->llm->setPrompt($this->getPrompt());
                    $this->llm->setStreaming($this->isStreaming());
                    break;
                case "custom":
                    $this->llm = new CustomAI($model);
//                    $this->llm->setApiKey($this->getApiKey());
                    $this->llm->setUrl($this->getURL());
                    $this->llm->setMaxMemoryMessages($this->getMaxMemoryMessages());
                    $this->llm->setPrompt($this->getPrompt());
                    break;
                default:
                    throw new AIChatException("AIChat::loadLLM() - LLM model provider not found (Provider: " . $provider . ") (Model: " . $model . ")");
            }
        } else {
            throw new AIChatException("AIChat::loadLLM() - LLM provider or model not found in config");
        }
    }
}
