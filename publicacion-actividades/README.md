# Publicación de Actividades (plugin)

## Qué hace

- Añade un formulario mediante shortcode: `[publicacion_actividades_form]`
- El formulario solo es accesible para los roles configurados
- Crea un **post** en estado **pending** (pendiente de revisión)
- Campo obligatorio: **desplegable de etiqueta** (tags) limitado a una lista predefinida
- Envía aviso por email al usuario solicitante cuando el post pase a **publish** (publicado)
- Permite avisar por email a una lista de correos cuando un usuario **envía** el formulario

## Instalación

1. Copia la carpeta `publicacion-actividades/` a:
   - `wp-content/plugins/publicacion-actividades/`
2. Activa el plugin en *Plugins*.
3. Ve a *Ajustes → Publicación Actividades*:
   - Selecciona roles permitidos
   - Selecciona las etiquetas permitidas
   - Configura emails de aviso al enviar (opcional)
   - Configura emails de aviso al publicarse (opcional)
4. Inserta el shortcode en una página.

## Notas

- El aviso de publicación solo se envía para posts creados por este formulario (se marca con meta `_pact_submission`).
- Variables disponibles en la plantilla de email: `{display_name}`, `{post_title}`, `{post_url}`
