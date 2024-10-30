# Análisis de Cambios Necesarios por Clase

## 1. objects/AIChat.php
- Eliminar lógica actual de comunicación directa con GPT
- Implementar nuevos métodos para manejar threads y runs usando OpenAI PHP client
- Añadir validación de assistant_id al cargar la configuración
- Modificar getLLMResponse() para usar threads y runs en lugar de chat completion
- Añadir métodos de gestión del ciclo de vida de threads
- Actualizar getters/setters para incluir assistant_id

## 2. objects/Chat.php → Thread.php
- Renombrar clase a Thread
- Actualizar propiedades para incluir thread_id de OpenAI
- Modificar métodos para reflejar la terminología de threads
- Actualizar referencias en otras clases que usen Chat
- Adaptar métodos de guardado/carga para usar nuevos campos
- Modificar toArray() para incluir datos de thread

## 3. objects/Message.php
- Añadir propiedad message_id para OpenAI
- Eliminar almacenamiento local del contenido del mensaje
- Añadir métodos para recuperar contenido vía API
- Actualizar save() para manejar message_id
- Modificar toArray() para obtener contenido de OpenAI
- Actualizar relaciones con Thread en lugar de Chat

## 4. class.ilObjAIChatGUI.php
- Actualizar processGetApiCall() para usar threads
- Modificar processPostApiCall() para crear/gestionar threads
- Adaptar apiCall() para manejar la nueva estructura
- Actualizar manejo de streaming si es necesario
- Modificar respuestas JSON para reflejar nueva estructura
- Actualizar métodos de UI para mostrar threads

## 5. platform/AIChatDatabase.php
- Actualizar constantes de tablas permitidas
- Modificar métodos para manejar nuevos campos
- Adaptar queries para nueva estructura
- Mantener compatibilidad con datos existentes
- Añadir validaciones para nuevos campos
- Actualizar manejo de índices y claves

## 6. class.ilAIChatConfigGUI.php
- Añadir campo assistant_id al formulario de configuración
- Implementar validación de assistant_id
- Añadir mensaje de error si assistant_id no existe
- Actualizar buildForm() con nuevo campo
- Modificar save() para validar assistant_id
- Actualizar transformaciones de datos