# Publicación de Actividades (plugin)

## Qué hace

- Envía por **correo electrónico** toda la solicitud enviada desde el formulario
- Incluye en el correo todos los campos del formulario en formato legible
- Permite adjuntar **una o varias imágenes** desde el formulario y las envía como adjuntos del correo
- Usa listas de etiquetas configuradas desde ajustes para los selectores de:
  - Tipo de actividad
  - Dojo solicitante
- Puede enviar el correo a destinatarios principales, destinatarios adicionales y opcionalmente al propio solicitante

## Instalación

1. Copia la carpeta `publicacion-actividades/` a:
   - `wp-content/plugins/publicacion-actividades/`
2. Activa el plugin en _Plugins_.
3. Ve a _Ajustes → Publicación Actividades_:
   - Selecciona roles permitidos
   - Selecciona las etiquetas permitidas para:
     - Tipo de actividad
     - Dojo solicitante
   - Configura destinatarios y plantillas del correo principal
   - Configura, si quieres, una copia al solicitante
4. Inserta el shortcode en una página:
   - `[publicacion_actividades_form]`

## Notas

- El plugin ya no crea entradas de WordPress; el envío se resuelve íntegramente por email.
- La opción de categoría por defecto se mantiene en ajustes por compatibilidad, pero ya no tiene efecto.
- Variables disponibles en las plantillas: `{display_name}`, `{user_email}`, `{post_title}`, `{submission_summary}`, `{tipo_actividad}`, `{dojo_solicitante}`, `{fecha}`, `{hora}`, `{lugar}`, `{aportacion}`, `{email_contacto}`, `{persona_contacto}`, `{telefono_contacto}`, `{descripcion}`
