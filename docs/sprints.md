# Plan de Sprints - Migración OpenAI Assistants

## Sprint 1: Integración OpenAI PHP
### Objetivos
- Establecer base para comunicación con API OpenAI
- Implementar cliente wrapper

### Tareas
1. Composer require openai-php/client
2. Crear clase OpenAIClient wrapper
    - Implementar métodos para threads
    - Implementar métodos para mensajes
    - Implementar métodos para runs
3. Actualizar AIChat.php con métodos básicos de OpenAI
4. Pruebas de conexión con API

### Entregables
- Cliente OpenAI configurado y funcional
- Wrapper implementado
- Métodos base en AIChat.php

## Sprint 2: Preparación Base de Datos y Modelos Base
### Objetivos
- Actualizar estructura de base de datos
- Preparar clases base para nueva arquitectura

### Tareas
1. Actualizar dbupdate.php
    - Nuevas tablas para threads
    - Nuevos campos para OpenAI IDs
    - Modificar campos existentes
2. Renombrar y adaptar Chat.php a Thread.php
3. Actualizar AIChatDatabase.php
    - Nuevas constantes
    - Adaptación de métodos

### Entregables
- Base de datos actualizada
- Thread.php básico funcionando
- AIChatDatabase.php actualizado

## Sprint 3: Adaptación AIChat y OpenAI Integration
### Objetivos
- Simplificar AIChat.php eliminando funcionalidad redundante
- Integrar OpenAIClient para manejo de threads

### Tareas
1. Actualizar AIChat.php
   - Eliminar toda la lógica relacionada con mensajes y chats
   - Añadir campo y validación de assistant_id
   - Adaptar getters/setters necesarios
   - Mantener solo gestión de configuración por instancia

2. Integrar OpenAIClient
   - Implementar nuevo getLLMResponse() usando threads/runs
   - Implementar gestión del ciclo de vida de threads
   - Manejo de errores y timeouts

### Entregables
- AIChat.php simplificado
- Integración OpenAI completa y funcional

## Sprint 4: GUI y API Endpoints
### Objetivos
- Adaptar endpoints para trabajar con threads
- Actualizar interfaz de usuario

### Tareas
1. Modificar ilObjAIChatGUI
   - Adaptar apiCall() para trabajar con threads
   - Actualizar processGetApiCall()
   - Actualizar processPostApiCall()
   - Actualizar manejo de respuestas