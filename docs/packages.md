# Librerías propuestas

## OpenAI PHP

### Ejmplos de uso
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
