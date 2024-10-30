# AIChat Plugin

## Descripción
Este plugin permite crear un chatbot usando los modelos GPT-3.5 y GPT-4 de OpenAI. Se puede configurar el comportamiento del chatbot, como el número de mensajes a recordar, el máximo de caracteres por mensaje, el texto de descargo de responsabilidad y el modelo a utilizar.

## Tarea Actual
El plugin deberá usar la API de Asistentes de OpenAI para interactuar con el chatbot. El plugin debe poder enviar mensajes relacionados con documentos y recibir una respuesta personalizada (usando documentos del usuario) del chatbot.

## Reglas
- Debemos hacer cambios lo menos invasivos posible.
- Debemos implementar en código únicamente lo que no se pueda lograr usando OpenAI playground (crear el asistente y agregar archivos).

## Código fuente actual
```php
// classes/class.ilObjAIChat.php
use platform\AIChatException;
use objects\AIChat;

/**
 * Class ilObjAIChat
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 */
class ilObjAIChat extends ilObjectPlugin
{
    private AIChat $ai_chat;

    /**
     * Creates a new object
     * @param bool $clone_mode
     * @throws AIChatException
     */
    protected function doCreate(bool $clone_mode = false): void
    {
        $this->ai_chat = new AIChat($this->getId());

        $this->ai_chat->save();
    }

    /**
     * Read the object
     */
    protected function doRead(): void
    {
        $this->ai_chat = new AIChat($this->getId());
    }

    /**
     * Deletes the object
     * @throws AIChatException
     */
    protected function doDelete(): void
    {
        $this->ai_chat->delete();
    }

    /**
     * Updates the object
     * @throws AIChatException
     */
    protected function doUpdate(): void
    {
        $this->ai_chat->save();
    }

    protected function initType(): void
    {
        $this->setType(ilAIChatPlugin::PLUGIN_ID);
    }

    public function getAIChat(): AIChat
    {
        return $this->ai_chat;
    }
```
```php
// classes/class.ilAIChatPlugin.php
class ilAIChatPlugin extends ilRepositoryObjectPlugin
{
    const PLUGIN_ID = 'xaic';

    const PLUGIN_NAME = 'AIChat';
    protected function uninstallCustom(): void
    {
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }
    public function allowCopy(): bool
    {
        return true;
    }
}
```
```php
// classes/class.ilAIChatConfigGUI.php
use ILIAS\UI\Factory;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Renderer;
use platform\AIChatConfig;
use platform\AIChatException;

/**
 * Class ilAIChatConfigGUI
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 * @ilCtrl_IsCalledBy  ilAIChatConfigGUI: ilObjComponentSettingsGUI
 */
class ilAIChatConfigGUI extends ilPluginConfigGUI
{
    protected Factory $factory;
    protected Renderer $renderer;
    protected \ILIAS\Refinery\Factory $refinery;
    protected ilCtrl $control;
    protected ilGlobalTemplateInterface $tpl;
    protected $request;

    /**
     * @throws AIChatException
     * @throws ilException
     */
    public function performCommand($cmd): void
    {
        global $DIC;
        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->control = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->request = $DIC->http()->request();

        switch ($cmd) {
            case "configure":
                AIChatConfig::load();
                $this->control->setParameterByClass('ilAIChatConfigGUI', 'cmd', 'configure');
                $form_action = $this->control->getLinkTargetByClass("ilAIChatConfigGUI", "configure");
                $rendered = $this->renderForm($form_action, $this->buildForm());
                break;
            default:
                throw new ilException("command not defined");

        }

        $this->tpl->setContent($rendered);
    }

    /**
     * @throws AIChatException
     */
    private function buildForm(): array
    {
        $provider = $this->factory->input()->field()->switchableGroup(
            array(
                "openai" => $this->buildOpenAIGroup(),
                "custom" => $this->buildCustomGroup()
            ),
            $this->plugin_object->txt('config_provider')
        )->withValue(AIChatConfig::get("llm_provider") != "" ? AIChatConfig::get("llm_provider") : "openai")->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('llm_provider', $v[0]);
            }
        ))->withRequired(true);

        $api_section = $this->factory->input()->field()->section(
            array(
                $provider
            ),
            $this->plugin_object->txt('config_api_section')
        );

        $prompt_selection = $this->factory->input()->field()->textarea(
            $this->plugin_object->txt('config_prompt_selection'),
            $this->plugin_object->txt('config_prompt_selection_info')
        )->withValue((string) AIChatConfig::get("prompt_selection"))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('prompt_selection', $v);
            }
        ))->withRequired(true);

        $characters_limit = $this->factory->input()->field()->numeric(
            $this->plugin_object->txt('config_characters_limit'), $this->plugin_object->txt('config_characters_limit_info')
        )->withValue(AIChatConfig::get("characters_limit") != "" ? (int) AIChatConfig::get("characters_limit") : 100)->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('characters_limit', $v);
            }
        ))->withRequired(true);

        $n_memory_messages = $this->factory->input()->field()->numeric(
            $this->plugin_object->txt('config_n_memory_messages'), $this->plugin_object->txt('config_n_memory_messages_info')
        )->withValue(AIChatConfig::get("config_n_memory_messages") != "" ? (int) AIChatConfig::get("config_n_memory_messages") : 100)->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('n_memory_messages', $v);
            }
        ))->withRequired(true);

        $disclaimer_text = $this->factory->input()->field()->textarea(
            $this->plugin_object->txt('config_disclaimer_text'),
            $this->plugin_object->txt('config_disclaimer_text_info')
        )->withValue((string) AIChatConfig::get("disclaimer_text"))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('disclaimer_text', $v);
            }
        ))->withRequired(true);

        $general_section = $this->factory->input()->field()->section(
            array(
                $prompt_selection,
                $characters_limit,
                $n_memory_messages,
                $disclaimer_text,
            ),
            $this->plugin_object->txt('config_general_section')
        );

        return array(
            $api_section,
            $general_section
        );
    }

    /**
     * @throws AIChatException
     */
    private function buildOpenAIGroup(): Group
    {
        $models = array(
            "gpt-4o" => "GPT-4o",
            "gpt-4o-mini" => "GPT-4o mini",
            "gpt-4-turbo" => "GPT-4 Turbo",
            "gpt-4" => "GPT-4",
            "gpt-3.5-turbo" => "GPT-3.5 Turbo"
        );

        $model = $this->factory->input()->field()->select(
            $this->plugin_object->txt('config_model'),
            $models,
            $this->plugin_object->txt('config_model_info')
        )->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('llm_model', $v);
            }
        ))->withRequired(true);

        if (AIChatConfig::get("llm_model") != "") {
            if (array_key_exists(AIChatConfig::get("llm_model"), $models)) {
                $model = $model->withValue(AIChatConfig::get("llm_model"));
            }
        }

        $global_api_key = $this->factory->input()->field()->text(
            $this->plugin_object->txt('config_global_api_key')
        )->withValue((string) AIChatConfig::get("global_api_key"))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('global_api_key', $v);
            }
        ))->withRequired(true);

        $streaming_enabled = $this->factory->input()->field()->checkbox(
            $this->plugin_object->txt('config_streaming_enabled'),
            $this->plugin_object->txt('config_streaming_enabled_info')
        )->withValue((bool) AIChatConfig::get("streaming_enabled"))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('streaming_enabled', $v);
            }
        ));

        return $this->factory->input()->field()->group(
            array(
                $model,
                $global_api_key,
                $streaming_enabled
            ),
            $this->plugin_object->txt('config_openai')
        );
    }

    /**
     * @throws AIChatException
     */
    private function buildCustomGroup(): Group
    {
        $url = $this->factory->input()->field()->text(
            $this->plugin_object->txt('config_url'),
            $this->plugin_object->txt('config_url_info')
        )->withValue((string) AIChatConfig::get("llm_url"))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('llm_url', $v);
            }
        ))->withRequired(true);

        $model = $this->factory->input()->field()->text(
            $this->plugin_object->txt('config_model'),
            $this->plugin_object->txt('config_model_info')
        )->withValue((string) AIChatConfig::get("llm_model"))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                AIChatConfig::set('llm_model', $v);
            }
        ))->withRequired(true);

//        $global_api_key = $this->factory->input()->field()->text(
//            $this->plugin_object->txt('config_global_api_key')
//        )->withValue((string) AIChatConfig::get("global_api_key"))->withAdditionalTransformation($this->refinery->custom()->transformation(
//            function ($v) {
//                AIChatConfig::set('global_api_key', $v);
//            }
//        ));

        return $this->factory->input()->field()->group(
            array(
                $url,
                $model,
//                $global_api_key
            ),
            $this->plugin_object->txt('config_custom')
        );
    }

    /**
     * @throws AIChatException
     */
    private function renderForm(string $form_action, array $sections): string
    {
        $form = $this->factory->input()->container()->form()->standard(
            $form_action,
            $sections
        );

        $saving_info = "";

        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();
            if ($result) {
                $saving_info = $this->save();

                $form = $this->factory->input()->container()->form()->standard(
                    $form_action,
                    $this->buildForm()
                );
            }
        }

        return $saving_info . $this->renderer->render($form);
    }

    public function save(): string
    {
        AIChatConfig::save();
        return $this->renderer->render($this->factory->messageBox()->success($this->plugin_object->txt('config_msg_success')));
    }
}
```
```php
// classes/platform/AIChatDatabase

namespace platform;

use Exception;
use ilDBInterface;

/**
 * Class AIChatDatabase
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 */
class AIChatDatabase
{
    const ALLOWED_TABLES = [
        'xaic_config',
        'xaic_objects',
        'xaic_chats',
        'xaic_messages'
    ];

    private ilDBInterface $db;

    public function __construct()
    {
        global $DIC;

        $this->db = $DIC->database();
    }

    /**
     * Inserts a new row in the database
     *
     * Usage: AIChatDatabase::insert('table_name', ['column1' => 'value1', 'column2' => 'value2']);
     *
     * @param string $table
     * @param array $data
     * @return void
     * @throws AIChatException
     */
    public function insert(string $table, array $data): void
    {
        if (!$this->validateTableName($table)) {
            throw new AIChatException("Invalid table name: " . $table);
        }

        try {
            $this->db->query("INSERT INTO " . $table . " (" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", array_map(function ($value) {
                    return $this->db->quote($value);
                }, array_values($data))) . ")");
        } catch (Exception $e) {
            throw new AIChatException($e->getMessage());
        }
    }

    /**
     * Inserts a new row in the database, if the row already exists, updates it
     *
     * Usage: AIChatDatabase::insertOnDuplicatedKey('table_name', ['column1' => 'value1', 'column2' => 'value2']);
     *
     * @param string $table
     * @param array $data
     * @return void
     * @throws AIChatException
     */
    public function insertOnDuplicatedKey(string $table, array $data): void
    {
        if (!$this->validateTableName($table)) {
            throw new AIChatException("Invalid table name: " . $table);
        }

        try {
            $this->db->query("INSERT INTO " . $table . " (" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", array_map(function ($value) {
                    return $this->db->quote($value);
                }, array_values($data))) . ") ON DUPLICATE KEY UPDATE " . implode(", ", array_map(function ($key, $value) {
                    return $key . " = " . $value;
                }, array_keys($data), array_map(function ($value) {
                    return $this->db->quote($value);
                }, array_values($data)))));
        } catch (Exception $e) {
            throw new AIChatException($e->getMessage());
        }
    }

    /**
     * Updates a row/s in the database
     *
     * Usage: AIChatDatabase::update('table_name', ['column1' => 'value1', 'column2' => 'value2'], ['id' => 1]);
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return void
     * @throws AIChatException
     */
    public function update(string $table, array $data, array $where): void
    {
        if (!$this->validateTableName($table)) {
            throw new AIChatException("Invalid table name: " . $table);
        }

        try {
            $this->db->query("UPDATE " . $table . " SET " . implode(", ", array_map(function ($key, $value) {
                    return $key . " = " . $value;
                }, array_keys($data), array_map(function ($value) {
                    return $this->db->quote($value);
                }, array_values($data)))) . " WHERE " . implode(" AND ", array_map(function ($key, $value) {
                    return $key . " = " . $value;
                }, array_keys($where), array_map(function ($value) {
                    return $this->db->quote($value);
                }, array_values($where)))));
        } catch (Exception $e) {
            throw new AIChatException($e->getMessage());
        }
    }

    /**
     * Deletes a row/s in the database
     *
     * Usage: AIChatDatabase::delete('table_name', ['id' => 1]);
     *
     * @param string $table
     * @param array $where
     * @return void
     * @throws AIChatException
     */
    public function delete(string $table, array $where): void
    {
        if (!$this->validateTableName($table)) {
            throw new AIChatException("Invalid table name: " . $table);
        }

        try {
            $this->db->query("DELETE FROM " . $table . " WHERE " . implode(" AND ", array_map(function ($key, $value) {
                    return $key . " = " . $value;
                }, array_keys($where), array_map(function ($value) {
                    return $this->db->quote($value);
                }, array_values($where)))));
        } catch (Exception $e) {
            throw new AIChatException($e->getMessage());
        }
    }

    /**
     * Selects a row/s in the database
     *
     * Usage: AIChatDatabase::select('table_name', ['id' => 1]);
     *
     * @param string $table
     * @param array|null $where
     * @param array|null $columns
     * @param string|null $extra
     * @return array
     * @throws AIChatException
     */
    public function select(string $table, ?array $where = null, ?array $columns = null, ?string $extra = ""): array
    {
        if (!$this->validateTableName($table)) {
            throw new AIChatException("Invalid table name: " . $table);
        }

        try {
            $query = "SELECT " . (isset($columns) ? implode(", ", $columns) : "*") . " FROM " . $table;

            if (isset($where)) {
                $query .= " WHERE " . implode(" AND ", array_map(function ($key, $value) {
                        return $key . " = " . $value;
                    }, array_keys($where), array_map(function ($value) {
                        return $this->db->quote($value);
                    }, array_values($where))));
            }

            if (is_string($extra)) {
                $extra = strip_tags($extra);
                $query .= " " . $extra;
            }

            $result = $this->db->query($query);

            $rows = [];

            while ($row = $this->db->fetchAssoc($result)) {
                $rows[] = $row;
            }

            return $rows;
        } catch (Exception $e) {
            throw new AIChatException($e->getMessage());
        }
    }

    /**
     * Returns the next id for a table
     *
     * Usage: AIChatDatabase::nextId('table_name');
     *
     * @param string $table
     * @return int
     * @throws AIChatException
     */
    public function nextId(string $table): int
    {
        try {
            return (int) $this->db->nextId($table);
        } catch (Exception $e) {
            throw new AIChatException($e->getMessage());
        }
    }

    /**
     * Utility function to validate table names and column names against a list of allowed names.
     * This helps prevent SQL injection through malicious SQL segments being passed as table or column names.
     */
    private function validateTableName(string $identifier): bool
    {
        return in_array($identifier, self::ALLOWED_TABLES, true);
    }
}
```
```php
// classes/platform/AIChatConfig.php

namespace platform;


/**
 * Class AIChatConfig
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 */
class AIChatConfig
{
    private static array $config = [];
    private static array $updated = [];

    /**
     * Load the plugin configuration
     * @return void
     * @throws AIChatException
     */
    public static function load(): void
    {
        $config = (new AIChatDatabase)->select('xaic_config');

        foreach ($config as $row) {
            if (isset($row['value']) && $row['value'] !== '') {
                $json_decoded = json_decode($row['value'], true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $row['value'] = $json_decoded;
                }
            }

            self::$config[$row['name']] = $row['value'];
        }
    }

    /**
     * Set the plugin configuration value for a given key to a given value
     * @param string $key
     * @param $value
     * @return void
     */
    public static function set(string $key, $value): void
    {
        if (is_bool($value)) {
            $value = (int)$value;
        }

        if (!isset(self::$config[$key]) || self::$config[$key] !== $value) {
            self::$config[$key] = $value;
            self::$updated[$key] = true;
        }
    }

    /**
     * Gets the plugin configuration value for a given key
     * @param string $key
     * @return mixed|string
     * @throws AIChatException
     */
    public static function get(string $key)
    {
        return self::$config[$key] ?? self::getFromDB($key);
    }

    /**
     * Gets the plugin configuration value for a given key from the database
     * @param string $key
     * @return mixed|string
     * @throws AIChatException
     */
    public static function getFromDB(string $key)
    {
        $config = (new AIChatDatabase)->select('xaic_config', array(
            'name' => $key
        ));

        if (count($config) > 0) {
            $json_decoded = json_decode($config[0]['value'], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $config[0]['value'] = $json_decoded;
            }

            self::$config[$key] = $config[0]['value'];

            return $config[0]['value'];
        } else {
            return "";
        }
    }

    /**
     * Gets all the plugin configuration values
     * @return array
     */
    public static function getAll(): array
    {
        return self::$config;
    }

    /**
     * Save the plugin configuration if the parameter is updated
     * @return bool|string
     */
    public static function save()
    {
        foreach (self::$updated as $key => $exist) {
            if ($exist) {
                if (isset(self::$config[$key])) {
                    $data = array(
                        'name' => $key
                    );

                    if (is_array(self::$config[$key])) {
                        $data['value'] = json_encode(self::$config[$key]);
                    } else {
                        $data['value'] = self::$config[$key];
                    }

                    try {
                        (new AIChatDatabase)->insertOnDuplicatedKey('xaic_config', $data);

                        self::$updated[$key] = false;
                    } catch (AIChatException $e) {
                        return $e->getMessage();
                    }
                }
            }
        }

        // In case there is nothing to update, return true to avoid error messages
        return true;
    }
}
```
```php
// classes/objects/class.Message.php

namespace objects;

use DateTime;
use Exception;
use platform\AIChatDatabase;
use platform\AIChatException;

/**
 * Class Message
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 */
class Message
{
    /**
     * @var int
     */
    private int $id = 0;

    /**
     * @var int
     */
    private int $chat_id;

    /**
     * @var DateTime
     */
    private DateTime $date;

    /**
     * @var string
     */
    private string $role;

    /**
     * @var string
     */
    private string $message;

    public function __construct(?int $id = null)
    {
        $this->date = new DateTime();

        if ($id !== null && $id > 0) {
            $this->id = $id;

            $this->loadFromDB();
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getChatId(): int
    {
        return $this->chat_id;
    }

    public function setChatId(int $chat_id): void
    {
        $this->chat_id = $chat_id;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @throws AIChatException
     * @throws Exception
     */
    public function loadFromDB(): void
    {
        $database = new AIChatDatabase();

        $result = $database->select("xaic_messages", ["id" => $this->getId()]);

        if (isset($result[0])) {
            $this->setChatId((int)$result[0]["chat_id"]);
            $this->setDate(new DateTime($result[0]["date"]));
            $this->setRole($result[0]["role"]);
            $this->setMessage($result[0]["message"]);
        }
    }

    /**
     * @throws AIChatException
     */
    public function save(): void
    {
        $database = new AIChatDatabase();

        $data = [
            "chat_id" => $this->getChatId(),
            "date" => $this->getDate()->format("Y-m-d H:i:s"),
            "role" => $this->getRole(),
            "message" => $this->getMessage()
        ];

        if ($this->getId() > 0) {
            $database->update("xaic_messages", $data, ["id" => $this->getId()]);
        } else {
            $id = $database->nextId("xaic_messages");

            $this->setId($id);

            $data["id"] = $id;

            $database->insert("xaic_messages", $data);
        }
    }

    /**
     * @throws AIChatException
     */
    public function delete(): void
    {
        $database = new AIChatDatabase();

        $database->delete("xaic_messages", ["id" => $this->getId()]);
    }

    public function toArray(): array
    {
        return [
            "id" => $this->getId(),
            "chat_id" => $this->getChatId(),
            "role" => $this->getRole(),
            "content" => $this->getMessage()
        ];
    }
}
```
```php
//classes/class.ilObjAIChatAccess.php

use objects\AIChat;
use platform\AIChatException;

/**
 * Class ilObjAIChatAccess
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 */
class ilObjAIChatAccess extends ilObjectPluginAccess
{
    public static function hasWriteAccess($ref_id = null, $user_id = null): bool
    {
        return self::hasAccess('write', $ref_id, $user_id);
    }

    protected static function hasAccess(string $permission, $ref_id = null, $user_id = null): bool
    {
        global $DIC;
        $ref_id = (int)$ref_id ?: (int)$_GET['ref_id'];
        $user_id = $user_id ?: $DIC->user()->getId();

        return $DIC->access()->checkAccessOfUser($user_id, $permission, '', $ref_id);
    }

    /**
     * Check if the object is offline
     *
     * @param int $a_obj_id
     * @return bool
     */
    public static function _isOffline($a_obj_id): bool
    {
        $liveVoting = new AIChat((int) $a_obj_id);
        return !$liveVoting->isOnline();
    }
}
```
```php
//classes/class.ilObjAIChatGUI.php

use ILIAS\UI\Component\Input\Group;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use objects\AIChat;
use objects\Chat;
use objects\Message;
use platform\AIChatException;

/**
 * Class ilObjAIChatGUI
 * @authors Jesús Copado, Daniel Cazalla, Saúl Díaz, Juan Aguilar <info@surlabs.es>
 * @ilCtrl_isCalledBy ilObjAIChatGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjAIChatGUI: ilObjectCopyGUI, ilPermissionGUI, ilInfoScreenGUI, ilCommonActionDispatcherGUI
 */
class ilObjAIChatGUI extends ilObjectPluginGUI
{
    private Factory $factory;
    private Renderer $renderer;
    protected \ILIAS\Refinery\Factory $refinery;

    public function __construct($a_ref_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0)
    {
        global $DIC;

        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->refinery = $DIC->refinery();
        $this->request = $DIC->http()->request();

        parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);
    }

    public function getAfterCreationCmd(): string
    {
        return 'content';
    }

    public function getStandardCmd(): string
    {
        return 'content';
    }

    public function performCommand(string $cmd): void
    {
        $this->setTitleAndDescription();
        $this->{$cmd}();
    }

    public function getType(): string
    {
        return ilAIChatPlugin::PLUGIN_ID;
    }

    /**
     * @throws ilCtrlException
     */
    protected function setTabs(): void
    {
        $this->tabs->addTab("content", $this->plugin->txt("object_content"), $this->ctrl->getLinkTarget($this, "content"));

        if ($this->checkPermissionBool("write")) {
            $this->tabs->addTab("settings", $this->plugin->txt("object_settings"), $this->ctrl->getLinkTarget($this, "settings"));
        }

        if ($this->checkPermissionBool("edit_permission")) {
            $this->tabs->addTab("perm_settings", $this->lng->txt("perm_settings"), $this->ctrl->getLinkTargetByClass(array(
                get_class($this),
                "ilPermissionGUI",
            ), "perm"));
        }
    }

    /**
     * @throws ilTemplateException
     * @throws ilCtrlException
     */
    private function content()
    {
        global $DIC;
        $this->tabs->activateTab("content");
        $tpl = $DIC['tpl'];
        //$tpl = new ilTemplate("index.html", false, false, $this->plugin->getDirectory());
        $tpl->addCss($this->plugin->getDirectory() . "/templates/default/index.css");
        $tpl->addJavascript($this->plugin->getDirectory() . "/templates/default/index.js");

        $apiUrl = $this->ctrl->getLinkTargetByClass("ilObjAIChatGUI", "apiCall");

        $this->tpl->setContent("<div id='root' apiurl='$apiUrl'></div>");
    }

    /**
     * @throws AIChatException
     * @throws ilCtrlException
     */
    private function settings()
    {
        $this->tabs->activateTab("settings");

        $form_action = $this->ctrl->getLinkTargetByClass("ilObjAIChatGUI", "settings");
        $this->tpl->setContent($this->renderSettingsForm($form_action, $this->buildSettingsForm()));
    }

    private function renderSettingsForm(string $form_action, array $sections): string
    {
        $form = $this->factory->input()->container()->form()->standard(
            $form_action,
            $sections
        );

        $saving_info = "";

        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();
            if ($result) {
                $saving_info = $this->saveSettings();
            }
        }

        return $saving_info . $this->renderer->render($form);
    }

    /**
     * @throws AIChatException
     */
    private function buildSettingsForm(): array
    {
        /**
         * @var $aiChat AIChat
         */
        $aiChat = $this->object->getAIChat();

        $title_input = $this->factory->input()->field()->text(
            $this->plugin->txt('object_settings_title')
        )->withValue($this->object->getTitle())->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                $this->object->setTitle($v);
            }
        ));

        $description_input = $this->factory->input()->field()->textarea(
            $this->plugin->txt('object_settings_description')
        )->withValue($this->object->getDescription())->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) {
                $this->object->setDescription($v);
            }
        ));

        $online_input = $this->factory->input()->field()->checkbox(
            $this->plugin->txt('object_settings_online'),
            $this->plugin->txt('object_settings_online_info')
        )->withValue($aiChat->isOnline())->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setOnline($v);
            }
        ));

        $basic_section = $this->factory->input()->field()->section(
            array(
                $title_input,
                $description_input,
                $online_input
            ),
            $this->plugin->txt('object_settings_basic')
        );

        $provider = $this->factory->input()->field()->switchableGroup(
            array(
                "default" => $this->factory->input()->field()->group(array(), $this->plugin->txt('config_default')),
                "openai" => $this->buildOpenAIGroup(),
                "custom" => $this->buildCustomGroup()
            ),
            $this->plugin->txt('config_provider')
        )->withValue($aiChat->getProvider(true) != "" ? $aiChat->getProvider(true) : "default")->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setProvider($v[0]);
            }
        ));

        $api_section = $this->factory->input()->field()->section(
            array(
                $provider
            ),
            $this->plugin->txt('config_api_section')
        );

        $prompt_selection = $this->factory->input()->field()->textarea(
            $this->plugin->txt('config_prompt_selection'),
            $this->plugin->txt('config_prompt_selection_info')
        )->withValue($aiChat->getPrompt(true))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setPrompt($v);
            }
        ))->withOnloadCode(function ($id) use ($aiChat) {
            return "$('#$id').attr('placeholder', `{$aiChat->getPrompt()}`);";
        });

        $characters_limit = $this->factory->input()->field()->numeric(
            $this->plugin->txt('config_characters_limit'), $this->plugin->txt('config_characters_limit_info')
        )->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setCharLimit($v);
            }
        ))->withOnloadCode(function ($id) use ($aiChat) {
            return "$('#$id').attr('placeholder', '{$aiChat->getCharLimit()}');";
        });

        if ($aiChat->getCharLimit(true) > 0) {
            $characters_limit = $characters_limit->withValue($aiChat->getCharLimit(true));
        }

        $n_memory_messages = $this->factory->input()->field()->numeric(
            $this->plugin->txt('config_n_memory_messages'), $this->plugin->txt('config_n_memory_messages_info')
        )->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setMaxMemoryMessages($v);
            }
        ))->withOnloadCode(function ($id) use ($aiChat) {
            return "$('#$id').attr('placeholder', '{$aiChat->getMaxMemoryMessages()}');";
        });

        if ($aiChat->getMaxMemoryMessages(true) > 0) {
            $n_memory_messages = $n_memory_messages->withValue($aiChat->getMaxMemoryMessages(true));
        }

        $disclaimer_text = $this->factory->input()->field()->textarea(
            $this->plugin->txt('config_disclaimer_text'),
            $this->plugin->txt('config_disclaimer_text_info')
        )->withValue($aiChat->getDisclaimer(true))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setDisclaimer($v);
            }
        ))->withOnloadCode(function ($id) use ($aiChat) {
            return "$('#$id').attr('placeholder', `{$aiChat->getDisclaimer()}`);";
        });

        $general_section = $this->factory->input()->field()->section(
            array(
                $prompt_selection,
                $characters_limit,
                $n_memory_messages,
                $disclaimer_text,
            ),
            $this->plugin->txt('config_general_section')
        );

        return array(
            $basic_section,
            $api_section,
            $general_section,
        );
    }

    /**
     * @throws AIChatException
     */
    private function buildOpenAIGroup(): Group
    {
        /**
         * @var $aiChat AIChat
         */
        $aiChat = $this->object->getAIChat();

        $models = array(
            "gpt-4o" => "GPT-4o",
            "gpt-4o-mini" => "GPT-4o mini",
            "gpt-4-turbo" => "GPT-4 Turbo",
            "gpt-4" => "GPT-4",
            "gpt-3.5-turbo" => "GPT-3.5 Turbo"
        );

        $model = $this->factory->input()->field()->select(
            $this->plugin->txt('config_model'),
            $models,
            $this->plugin->txt('config_model_info')
        )->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setModel($v);
            }
        ))->withRequired(true);

        if ($aiChat->getModel(true) != "") {
            if (array_key_exists($aiChat->getModel(true), $models)) {
                $model = $model->withValue($aiChat->getModel(true));
            }
        }

        $global_api_key = $this->factory->input()->field()->text(
            $this->plugin->txt('config_global_api_key')
        )->withValue($aiChat->getApiKey(true))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setApiKey($v);
            }
        ))->withRequired(true);

        $streaming_enabled = $this->factory->input()->field()->checkbox(
            $this->plugin->txt('config_streaming_enabled'),
            $this->plugin->txt('config_streaming_enabled_info')
        )->withValue($aiChat->isStreaming(true))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setStreaming($v);
            }
        ));

        return $this->factory->input()->field()->group(
            array(
                $model,
                $global_api_key,
                $streaming_enabled
            ),
            $this->plugin->txt('config_openai')
        );
    }

    /**
     * @throws AIChatException
     */
    private function buildCustomGroup(): Group
    {
        /**
         * @var $aiChat AIChat
         */
        $aiChat = $this->object->getAIChat();

        $url = $this->factory->input()->field()->text(
            $this->plugin->txt('config_url'),
            $this->plugin->txt('config_url_info')
        )->withValue($aiChat->getUrl(true))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setUrl($v);
            }
        ))->withRequired(true);

        $model = $this->factory->input()->field()->text(
            $this->plugin->txt('config_model'),
            $this->plugin->txt('config_model_info')
        )->withValue($aiChat->getModel(true))->withAdditionalTransformation($this->refinery->custom()->transformation(
            function ($v) use ($aiChat) {
                $aiChat->setModel($v);
            }
        ))->withRequired(true);

//        $global_api_key = $this->factory->input()->field()->text(
//            $this->plugin->txt('config_global_api_key')
//        )->withValue($aiChat->getApiKey(true))->withAdditionalTransformation($this->refinery->custom()->transformation(
//            function ($v) use ($aiChat) {
//                $aiChat->setApiKey($v);
//            }
//        ))->withRequired(true);

        return $this->factory->input()->field()->group(
            array(
                $url,
                $model,
//                $global_api_key
            ),
            $this->plugin->txt('config_custom')
        );
    }

    private function saveSettings(): string
    {
        global $DIC;

        $renderer = $DIC->ui()->renderer();

        $this->object->update();

        return $renderer->render($DIC->ui()->factory()->messageBox()->success($this->plugin->txt('object_settings_msg_success')));
    }

    /**
     * @throws AIChatException
     */
    public function apiCall()
    {
        if ($this->request->getMethod() == "GET") {
            self::sendApiResponse($this->processGetApiCall($_GET));
        } else if ($this->request->getMethod() == "POST") {
            $postData = $this->request->getParsedBody();
            self::sendApiResponse($this->processPostApiCall($postData));
        } else {
            self::sendApiResponse(array("error" => "Method not allowed"), 405);
        }
    }

    /**
     * @throws AIChatException
     */
    private function processGetApiCall($data)
    {
        switch ($data["action"]) {
            case "config":
                /**
                 * @var $aiChat AIChat
                 */
                $aiChat = $this->object->getAIChat();

                return array(
                    "disclaimer" => $aiChat->getDisclaimer() ?? false,
                    "prompt_selection" => $aiChat->getPrompt() ?? false,
                    "characters_limit" => $aiChat->getCharLimit() ?? false,
                    "n_memory_messages" => $aiChat->getMaxMemoryMessages() ?? false,
                    "streaming_enabled" => $aiChat->isStreaming() ?? false,
                    "lang" => $this->lng->getUserLanguage(),
                    "translations" => $this->loadFrontLang()
                );
            case "chats":
                global $DIC;

                $user_id = $DIC->user()->getId();

                return $this->object->getAIChat()->getChatsForApi($user_id);
            case "chat":
                if (isset($data["chat_id"])) {
                    $chat = new Chat((int) $data["chat_id"]);

                    $chat->setMaxMessages($this->object->getAIChat()->getMaxMemoryMessages());

                    return $chat->toArray();
                } else {
                    self::sendApiResponse(array("error" => "Chat ID not provided"), 400);
                }
        }

        return false;
    }

    /**
     * @throws AIChatException
     */
    private function processPostApiCall($data)
    {
        switch ($data["action"]) {
            case "new_chat":
                global $DIC;

                $chat = new Chat();

                $user_id = $DIC->user()->getId();

                $chat->setUserId($user_id);
                $chat->setObjId($this->object->getId());
                $chat->setMaxMessages($this->object->getAIChat()->getMaxMemoryMessages());

                $chat->save();

                return $chat->toArray();
            case "add_message":
                if (isset($data["chat_id"]) && isset($data["message"])) {
                    $chat = new Chat((int) $data["chat_id"]);

                    $message = new Message();

                    $message->setChatId((int) $data["chat_id"]);
                    $message->setMessage($data["message"]);
                    $message->setRole("user");

                    if (count($chat->getMessages()) == 0) {
                        $chat->setTitleFromMessage($data["message"]);
                    }

                    $chat->addMessage($message);

                    $chat->setLastUpdate($message->getDate());

                    $chat->setMaxMessages($this->object->getAIChat()->getMaxMemoryMessages());

                    $retval = array(
                        "message" => $message->toArray(),
                        "llmresponse" => $this->object->getAIChat()->getLLMResponse($chat)->toArray()
                    );

                    $message->save();

                    $chat->save();

                    return $retval;
                } else {
                    self::sendApiResponse(array("error" => "Chat ID or message not provided"), 400);
                    break;
                }
            case "delete_chat":
                if (isset($data["chat_id"])) {
                    $chat = new Chat((int) $data["chat_id"]);

                    $chat->delete();

                    return true;
                } else {
                    self::sendApiResponse(array("error" => "Chat ID not provided"), 400);
                }
        }

        return false;
    }

    private function loadFrontLang(): array
    {
        return array(
            "front_new_chat_button" => $this->plugin->txt("front_new_chat_button"),
            "front_input_placeholder" => $this->plugin->txt("front_input_placeholder")
        );
    }

    public static function sendApiResponse($data, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-type: application/json');
        echo json_encode($data);
        exit();
    }
}
```
```php
//classes/class.ilObjAIChatListGUI.php

class ilObjAIChatListGUI extends ilObjectPluginListGUI
{
    public function getGuiClass(): string
    {
        return 'ilObjAIChatGUI';
    }

    public function initCommands(): array
    {
        return [
            [
                "permission" => "read",
                "cmd" => "content",
                "default" => true,
            ],
            [
                "permission" => "write",
                "cmd" => "settings",
                "txt" => $this->txt("object_settings")
            ]
        ];
    }

    public function initType()
    {
        $this->setType(ilAIChatPlugin::PLUGIN_ID);
    }

    public function getCustomProperties($a_prop): array
    {
        if (!isset($this->obj_id)) {
            return [];
        }

        $props = parent::getCustomProperties($a_prop);

        if (ilObjAIChatAccess::_isOffline($this->obj_id)) {
            $props[] = array(
                'alert' => true,
                'newline' => true,
                'property' => 'Status',
                'value' => 'Offline'
            );
        }

        return $props;
    }

    public function getAlertProperties(): array
    {
        if (!isset($this->obj_id)) {
            return [];
        }

        $props = parent::getAlertProperties();

        if (ilObjAIChatAccess::_isOffline($this->obj_id)) {
            $props[] = array(
                'alert' => true,
                'newline' => true,
                'property' => 'Status',
                'value' => 'Offline'
            );
        }

        return $props;
    }
}
```

## Cambios propuestos para adaptar dbupdate.php a OpenAI Assistants

### 1. Cambios Estructurales

#### Renombramientos
- Tabla `xaic_chats` renombrada a `xaic_threads`
- Campo `chat_id` renombrado a `thread_id` en la tabla `xaic_messages`

#### Nuevos Campos
- `assistant_id` añadido a `xaic_objects`
- `thread_id` añadido a `xaic_threads`
- `message_id` añadido a `xaic_messages`

#### Eliminaciones
- Eliminado campo `message` de `xaic_messages` (ahora se recuperará vía API)

### 2. Código Final Actualizado
```php
<?php
global $DIC;
$db = $DIC->database();

if (!$db->tableExists('xaic_config')) {
    $fields = [
        'name' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => true
        ],
        'value' => [
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ]
    ];

    $db->createTable('xaic_config', $fields);
    $db->addPrimaryKey('xaic_config', ['name']);
}

if (!$db->tableExists('xaic_objects')) {
    $fields = [
        'id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'online' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ],
        'api_key' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => false
        ],
        'assistant_id' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => false
        ],
        'provider' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => false
        ],
        'model' => [
            'type' => 'text', 
            'length' => 250,
            'notnull' => false
        ],
        'streaming' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ],
        'url' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => false
        ],
        'prompt' => [
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ],
        'char_limit' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ],
        'max_memory_messages' => [
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ],
        'disclaimer' => [
            'type' => 'text',
            'length' => 4000,
            'notnull' => false
        ]
    ];

    $db->createTable('xaic_objects', $fields);
    $db->addPrimaryKey('xaic_objects', ['id']);
}

if (!$db->tableExists('xaic_threads')) {
    $fields = [
        'id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'obj_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'title' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => true
        ],
        'thread_id' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => false
        ],
        'created_at' => [
            'type' => 'timestamp',
            'notnull' => true
        ],
        'user_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'last_update' => [
            'type' => 'timestamp',
            'notnull' => true
        ],
    ];

    $db->createTable('xaic_threads', $fields);
    $db->addPrimaryKey('xaic_threads', ['id']);
    $db->addIndex('xaic_threads', ['obj_id'], 'i_1');
    $db->createSequence('xaic_threads');
}

if (!$db->tableExists('xaic_messages')) {
    $fields = [
        'id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'thread_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ],
        'message_id' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => false
        ],
        'date' => [
            'type' => 'timestamp',
            'notnull' => true
        ],
        'role' => [
            'type' => 'text',
            'length' => 250,
            'notnull' => true
        ]
    ];

    $db->createTable('xaic_messages', $fields);
    $db->addPrimaryKey('xaic_messages', ['id']);
    $db->addIndex('xaic_messages', ['thread_id'], 'i_2');
    $db->createSequence('xaic_messages');
}
?>
```

### 3. Notas Adicionales

- Los IDs de OpenAI (thread_id, message_id, assistant_id) se almacenan como texto con longitud 250
- No se almacena el contenido de los mensajes localmente, se recuperarán vía API
- Se mantienen las secuencias y claves primarias existentes

## Librería propuesta

### OpenAI PHP

#### Ejmplos de uso
- Crear cliente de OpenAI
```php
$yourApiKey = getenv('YOUR_API_KEY');

$client = OpenAI::factory()
    ->withApiKey($yourApiKey)
    ->withOrganization('your-organization') // default: null
    ->withProject('Your Project') // default: null
    ->withBaseUri('openai.example.com/v1') // default: api.openai.com/v1
    ->withHttpClient($client = new \GuzzleHttp\Client([])) // default: HTTP client found using PSR-18 HTTP Client Discovery
    ->withHttpHeader('X-My-Header', 'foo')
    ->withQueryParam('my-param', 'bar')
    ->withStreamHandler(fn (RequestInterface $request): ResponseInterface => $client->send($request, [
        'stream' => true // Allows to provide a custom stream handler for the http client.
    ]))
    ->make();
```
- Obtener asistente
```php
$response = $client->assistants()->retrieve('asst_gxzBkD1wkKEloYqZ410pT5pd');

$response->id; // 'asst_gxzBkD1wkKEloYqZ410pT5pd'
$response->object; // 'assistant'
$response->createdAt; // 1623936000
$response->name; // 'Math Tutor'
$response->instructions; // 'You are a personal math tutor. When asked a question, write and run Python code to answer the question.'
$response->model; // 'gpt-4'
$response->description; // null
$response->tools[0]->type; // 'code_interpreter'
$response->toolResources; // []
$response->metadata; // []
$response->temperature: // null
$response->topP: // null
$response->format: // 'auto'

$response->toArray(); // ['id' => 'asst_gxzBkD1wkKEloYqZ410pT5pd', ...]
```
- Crear un thread
```php
$response = $client->threads()->create([]);

$response->id; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->object; // 'thread'
$response->createdAt; // 1623936000
$response->toolResources; // null
$response->metadata; // []

$response->toArray(); // ['id' => 'thread_tKFLqzRN9n7MnyKKvc1Q7868', ...]
```
- Crear y correr un thread
```php
$response = $client->threads()->createAndRun(
    [
        'assistant_id' => 'asst_gxzBkD1wkKEloYqZ410pT5pd',
        'thread' => [
            'messages' =>
                [
                    [
                        'role' => 'user',
                        'content' => 'Explain deep learning to a 5 year old.',
                    ],
                ],
        ],
    ],
);

$response->id; // 'run_4RCYyYzX9m41WQicoJtUQAb8'
$response->object; // 'thread.run'
$response->createdAt; // 1623936000
$response->assistantId; // 'asst_gxzBkD1wkKEloYqZ410pT5pd'
$response->threadId; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->status; // 'queued'
$response->requiredAction; // null
$response->lastError; // null
$response->startedAt; // null
$response->expiresAt; // 1699622335
$response->cancelledAt; // null
$response->failedAt; // null
$response->completedAt; // null
$response->incompleteDetails; // null
$response->lastError; // null
$response->model; // 'gpt-4'
$response->instructions; // null
$response->tools; // []
$response->metadata; // []
$response->usage->total_tokens; // 579
$response->temperature; // null
$response->topP; // null
$response->maxPromptTokens; // 1000
$response->maxCompletionTokens; // 1000
$response->truncationStrategy->type; // 'auto'
$response->responseFormat; // 'auto'
$response->toolChoice; // 'auto'

$response->toArray(); // ['id' => 'run_4RCYyYzX9m41WQicoJtUQAb8', ...]
```
- Obtener un thread
```php
$response = $client->threads()->retrieve('thread_tKFLqzRN9n7MnyKKvc1Q7868');

$response->id; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->object; // 'thread'
$response->createdAt; // 1623936000
$response->toolResources; // null
$response->metadata; // []

$response->toArray(); // ['id' => 'thread_tKFLqzRN9n7MnyKKvc1Q7868', ...]
```
- Modificar un thread
```php
$response = $client->threads()->modify('thread_tKFLqzRN9n7MnyKKvc1Q7868', [
        'metadata' => [
            'name' => 'My new thread name',
        ],
    ]);

$response->id; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->object; // 'thread'
$response->createdAt; // 1623936000
$response->toolResources; // null
$response->metadata; // ['name' => 'My new thread name']

$response->toArray(); // ['id' => 'thread_tKFLqzRN9n7MnyKKvc1Q7868', ...]
```
- Borra un thread
```php
$response = $client->threads()->delete('thread_tKFLqzRN9n7MnyKKvc1Q7868');

$response->id; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->object; // 'thread.deleted'
$response->deleted; // true

$response->toArray(); // ['id' => 'thread_tKFLqzRN9n7MnyKKvc1Q7868', ...]
```
- Crear un mensaje
```php
$response = $client->threads()->messages()->create('thread_tKFLqzRN9n7MnyKKvc1Q7868', [
    'role' => 'user',
    'content' => 'What is the sum of 5 and 7?',
]);

$response->id; // 'msg_SKYwvF3zcigxthfn6F4hnpdU'
$response->object; // 'thread.message'
$response->createdAt; // 1623936000
$response->threadId; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->status; // 'in_progress
$response->incompleteDetails; // null
$response->completedAt; // null
$response->incompleteAt; // null
$response->role; // 'user'
$response->content[0]->type; // 'text'
$response->content[0]->text->value; // 'What is the sum of 5 and 7?'
$response->content[0]->text->annotations; // []
$response->assistantId; // null
$response->runId; // null
$response->attachments; // []
$response->metadata; // []

$response->toArray(); // ['id' => 'msg_SKYwvF3zcigxthfn6F4hnpdU', ...]
```
- Obtener un mensaje
```php
$response = $client->threads()->messages()->retrieve(
    threadId: 'thread_tKFLqzRN9n7MnyKKvc1Q7868',
    messageId: 'msg_SKYwvF3zcigxthfn6F4hnpdU',
);

$response->id; // 'msg_SKYwvF3zcigxthfn6F4hnpdU'
$response->object; // 'thread.message'
$response->createdAt; // 1623936000
$response->threadId; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->status; // 'in_progress
$response->incompleteDetails; // null
$response->completedAt; // null
$response->incompleteAt; // null
$response->role; // 'user'
$response->content[0]->type; // 'text'
$response->content[0]->text->value; // 'What is the sum of 5 and 7?'
$response->content[0]->text->annotations; // []
$response->assistantId; // null
$response->runId; // null
$response->attachments; // []
$response->metadata; // []

$response->toArray(); // ['id' => 'msg_SKYwvF3zcigxthfn6F4hnpdU', ...]
```
- Modificar un mensaje
```php
$response = $client->threads()->messages()->modify(
    threadId: 'thread_tKFLqzRN9n7MnyKKvc1Q7868',
    messageId: 'msg_SKYwvF3zcigxthfn6F4hnpdU',
    parameters:  [
        'metadata' => [
            'name' => 'My new message name',
        ],
    ],
);

$response->id; // 'msg_SKYwvF3zcigxthfn6F4hnpdU'
$response->object; // 'thread.message'
$response->createdAt; // 1623936000
$response->threadId; // 'thread_tKFLqzRN9n7MnyKKvc1Q7868'
$response->status; // 'in_progress
$response->incompleteDetails; // null
$response->completedAt; // null
$response->incompleteAt; // null
$response->role; // 'user'
$response->content[0]->type; // 'text'
$response->content[0]->text->value; // 'What is the sum of 5 and 7?'
$response->content[0]->text->annotations; // []
$response->assistantId; // null
$response->runId; // null
$response->attachments; // []
$response->metadata; // ['name' => 'My new message name']

$response->toArray(); // ['id' => 'msg_SKYwvF3zcigxthfn6F4hnpdU', ...]
```
- Borrar un mensaje
```php
$response = $client->threads()->messages()->delete(
    threadId: 'thread_tKFLqzRN9n7MnyKKvc1Q7868',
    messageId: 'msg_SKYwvF3zcigxthfn6F4hnpdU'
);

$response->id; // 'msg_SKYwvF3zcigxthfn6F4hnpdU'
$response->object; // 'thread.message.deleted'
$response->deleted; // true

$response->toArray(); // ['id' => 'msg_SKYwvF3zcigxthfn6F4hnpdU', ...]
```

## Cambios propuestos

### 1. objects/AIChat.php
- Eliminar lógica actual de comunicación directa con GPT
- Implementar nuevos métodos para manejar threads y runs usando OpenAI PHP client
- Añadir validación de assistant_id al cargar la configuración
- Modificar getLLMResponse() para usar threads y runs en lugar de chat completion
- Añadir métodos de gestión del ciclo de vida de threads
- Actualizar getters/setters para incluir assistant_id

### 2. objects/Chat.php → Thread.php
- Renombrar clase a Thread
- Actualizar propiedades para incluir thread_id de OpenAI
- Modificar métodos para reflejar la terminología de threads
- Actualizar referencias en otras clases que usen Chat
- Adaptar métodos de guardado/carga para usar nuevos campos
- Modificar toArray() para incluir datos de thread

### 3. objects/Message.php
- Añadir propiedad message_id para OpenAI
- Eliminar almacenamiento local del contenido del mensaje
- Añadir métodos para recuperar contenido vía API
- Actualizar save() para manejar message_id
- Modificar toArray() para obtener contenido de OpenAI
- Actualizar relaciones con Thread en lugar de Chat

### 4. class.ilObjAIChatGUI.php
- Actualizar processGetApiCall() para usar threads
- Modificar processPostApiCall() para crear/gestionar threads
- Adaptar apiCall() para manejar la nueva estructura
- Actualizar manejo de streaming si es necesario
- Modificar respuestas JSON para reflejar nueva estructura
- Actualizar métodos de UI para mostrar threads

### 5. platform/AIChatDatabase.php
- Actualizar constantes de tablas permitidas
- Modificar métodos para manejar nuevos campos
- Adaptar queries para nueva estructura
- Mantener compatibilidad con datos existentes
- Añadir validaciones para nuevos campos
- Actualizar manejo de índices y claves

### 6. class.ilAIChatConfigGUI.php
- Añadir campo assistant_id al formulario de configuración
- Implementar validación de assistant_id
- Añadir mensaje de error si assistant_id no existe
- Actualizar buildForm() con nuevo campo
- Modificar save() para validar assistant_id
- Actualizar transformaciones de datos



## Sprints propuestos

### Sprint 1: Integración OpenAI PHP

#### Objetivos
- Establecer base para comunicación con API OpenAI
- Implementar cliente wrapper

#### Tareas
1. Composer require openai-php/client
2. Crear clase OpenAIClient wrapper
    - Implementar métodos para threads
    - Implementar métodos para mensajes
    - Implementar métodos para runs
3. Actualizar AIChat.php con métodos básicos de OpenAI
4. Pruebas de conexión con API

#### Entregables
- Cliente OpenAI configurado y funcional
- Wrapper implementado
- Métodos base en AIChat.php

### Sprint 2: Preparación Base de Datos y Modelos Base
#### Objetivos
- Actualizar estructura de base de datos
- Preparar clases base para nueva arquitectura

#### Tareas
1. Actualizar dbupdate.php
    - Nuevas tablas para threads
    - Nuevos campos para OpenAI IDs
    - Modificar campos existentes
2. Renombrar y adaptar Chat.php a Thread.php
3. Actualizar AIChatDatabase.php
    - Nuevas constantes
    - Adaptación de métodos

#### Entregables
- Base de datos actualizada
- Thread.php básico funcionando
- AIChatDatabase.php actualizado

### Sprint 3: Adaptación Modelos Core
#### Objetivos
- Implementar nueva lógica de negocio
- Adaptar modelos a OpenAI Assistants

#### Tareas
1. Completar Thread.php
    - Lógica de threads OpenAI
    - Gestión de mensajes
2. Actualizar Message.php
    - Integración con OpenAI messages
    - Manejo de message_id
3. Implementar nuevos getters/setters

#### Entregables
- Modelos core funcionando con OpenAI
- Integración threads-mensajes completa

### Sprint 4: GUI y Configuración
#### Objetivos
- Actualizar interfaces de usuario
- Implementar nueva configuración

#### Tareas
1. Modificar ilAIChatConfigGUI
    - Campo assistant_id
    - Validaciones
2. Actualizar ilObjAIChatGUI
    - Nueva lógica de threads
    - Adaptación API endpoints
3. Ajustes de interfaz

#### Entregables
- Interfaces actualizadas
- API endpoints funcionando
- Configuración completa

### Sprint 5: Migración y Cierre
#### Objetivos
- Asegurar transición de datos
- Finalizar implementación

#### Tareas
1. Crear script de migración
    - Chats a threads
    - Mensajes a nuevo formato
2. Pruebas de flujo completo
3. Corrección de bugs
4. Documentación

#### Entregables
- Script de migración funcional
- Sistema completo operativo
- Documentación actualizada