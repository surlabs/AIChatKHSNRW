# Cambios para adaptar a OpenAI Assistants

## 1. Cambios Estructurales

### Renombramientos
- Tabla `xaic_chats` renombrada a `xaic_threads`
- Campo `chat_id` renombrado a `thread_id` en la tabla `xaic_messages`

### Nuevos Campos
- `assistant_id` añadido a `xaic_objects`
- `thread_id` añadido a `xaic_threads`
- `message_id` añadido a `xaic_messages`

### Eliminaciones
- Eliminado campo `message` de `xaic_messages` (ahora se recuperará vía API)

## 2. Código Final Actualizado

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

## 3. Notas Adicionales

- Los IDs de OpenAI (thread_id, message_id, assistant_id) se almacenan como texto con longitud 250
- No se almacena el contenido de los mensajes localmente, se recuperarán vía API
- Se mantienen las secuencias y claves primarias existentes
