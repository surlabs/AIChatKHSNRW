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

## Sprint 3: Adaptación Modelos Core
### Objetivos
- Implementar nueva lógica de negocio
- Adaptar modelos a OpenAI Assistants

### Tareas
1. Completar Thread.php
    - Lógica de threads OpenAI
    - Gestión de mensajes
2. Actualizar Message.php
    - Integración con OpenAI messages
    - Manejo de message_id
3. Implementar nuevos getters/setters

### Entregables
- Modelos core funcionando con OpenAI
- Integración threads-mensajes completa

## Sprint 4: GUI y Configuración
### Objetivos
- Actualizar interfaces de usuario
- Implementar nueva configuración

### Tareas
1. Modificar ilAIChatConfigGUI
    - Campo assistant_id
    - Validaciones
2. Actualizar ilObjAIChatGUI
    - Nueva lógica de threads
    - Adaptación API endpoints
3. Ajustes de interfaz

### Entregables
- Interfaces actualizadas
- API endpoints funcionando
- Configuración completa

## Sprint 5: Migración y Cierre
### Objetivos
- Asegurar transición de datos
- Finalizar implementación

### Tareas
1. Crear script de migración
    - Chats a threads
    - Mensajes a nuevo formato
2. Pruebas de flujo completo
3. Corrección de bugs
4. Documentación

### Entregables
- Script de migración funcional
- Sistema completo operativo
- Documentación actualizada