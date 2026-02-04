# Publicación de Actividades (plugin)

## Qué hace

- El contenido del post se genera como **bloques de Gutenberg** (encabezados/listas/párrafos)

## Instalación

1. Copia la carpeta `publicacion-actividades/` a:
   - `wp-content/plugins/publicacion-actividades/`
2. Activa el plugin en _Plugins_.
3. Ve a _Ajustes → Publicación Actividades_:
   - Selecciona roles permitidos
   - Selecciona las etiquetas permitidas
   - Selecciona la categoría por defecto (opcional)
   - Configura emails de aviso al enviar (opcional)
   - Configura emails de aviso al publicarse (opcional)
4. Inserta el shortcode en una página.

## Notas

- El aviso de publicación solo se envía para posts creados por este formulario (se marca con meta `_pact_submission`).
- Variables disponibles en la plantilla de email: `{display_name}`, `{post_title}`, `{post_url}`
