# Publicación de Actividades (plugin)

## Qué hace

- El contenido del post se genera como **bloques de Gutenberg** (encabezados/listas/párrafos)
- Crea una entrada en estado **Pendiente de revisión** (pending)
- Asigna **etiquetas** configuradas desde ajustes (2 listas: “Tipo actividad” y “Dojo solicitante”)
  - Nota: estas selecciones se usan para etiquetar el post, pero no se imprimen dentro del bloque “Datos de la actividad”
- Permite adjuntar **una o varias imágenes** desde el formulario
  - Si se adjunta 1 imagen: se inserta un bloque `Imagen`
  - Si se adjuntan 2+ imágenes: se inserta un bloque `Galería`
  - La primera imagen se establece como **imagen destacada** (si el post no tenía)
- Puede enviar emails de aviso al enviar y/o al publicarse (configurable)

## Instalación

1. Copia la carpeta `publicacion-actividades/` a:
   - `wp-content/plugins/publicacion-actividades/`
2. Activa el plugin en _Plugins_.
3. Ve a _Ajustes → Publicación Actividades_:
   - Selecciona roles permitidos
   - Selecciona las etiquetas permitidas para:
     - Tipo de actividad
     - Dojo solicitante
   - Selecciona la categoría por defecto (opcional)
   - Configura emails de aviso al enviar (opcional)
   - Configura emails de aviso al publicarse (opcional)
4. Inserta el shortcode en una página:
   - `[publicacion_actividades_form]`

## Notas

- El aviso de publicación solo se envía para posts creados por este formulario (se marca con meta `_pact_submission`).
- Variables disponibles en la plantilla de email: `{display_name}`, `{post_title}`, `{post_url}`
