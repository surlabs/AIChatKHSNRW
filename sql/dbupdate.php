<#1>
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
        'user_id' => [
            'type' => 'integer',
            'length' => 8,
            'notnull' => true
        ]
    ];

    $db->createTable('xaic_threads', $fields);
    $db->addPrimaryKey('xaic_threads', ['id']);
    $db->addIndex('xaic_threads', ['obj_id'], 'i_1');
    $db->createSequence('xaic_threads');
}
?>

<#2>
<?php
global $DIC;
$db = $DIC->database();
if ($db->tableExists('xaic_config')) {

    $result = $db->query("SELECT value FROM xaic_config WHERE name = 'llm_model'");

    while ($row = $db->fetchAssoc($result)) {
        $model = str_replace('openai_', '', $row['value']);

        $db->manipulate("UPDATE xaic_config SET value = '$model' WHERE name = 'llm_model'");
    }
}
?>

<#3>
<?php
global $DIC;
$db = $DIC->database();
if ($db->tableExists('xaic_objects')) {
    $db->addTableColumn('xaic_objects', 'provider', [
        'type' => 'text',
        'length' => 250,
        'notnull' => false
    ]);

    $db->addTableColumn('xaic_objects', 'model', [
        'type' => 'text',
        'length' => 250,
        'notnull' => false
    ]);

    $db->addTableColumn('xaic_objects', 'streaming', [
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ]);

    $db->addTableColumn('xaic_objects', 'url', [
        'type' => 'text',
        'length' => 250,
        'notnull' => false
    ]);

    $db->addTableColumn('xaic_objects', 'prompt', [
        'type' => 'text',
        'length' => 4000,
        'notnull' => false
    ]);

    $db->addTableColumn('xaic_objects', 'char_limit', [
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ]);

    $db->addTableColumn('xaic_objects', 'max_memory_messages', [
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ]);
}
?>