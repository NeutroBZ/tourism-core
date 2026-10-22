# Tourism Core

## Videos y reseñas (0.6.2)

El editor de tours incorpora **Videos y reseñas** como una sección junto a las demás. Usa **Añadir video**, **Añadir reseña** y sus botones **Eliminar** para gestionar varias filas sin guardar entre cada una. Guarda el tour para aplicar los cambios. Los videos usan enlaces de YouTube; las reseñas nuevas requieren aprobación explícita antes de publicarse. La eliminación conserva la identidad de las filas restantes. Sin JavaScript siguen disponibles la fila vacía y las casillas de eliminación.

## Actualizaciones (corregidas en 0.6.3)

Incluye actualizaciones desde GitHub Releases mediante WordPress y publicación del ZIP al subir una etiqueta de versión. Requiere instalar 0.6.3 o posterior una vez en cada sitio; después permite **Actualizar ahora** o activar las actualizaciones automáticas de WordPress. Las versiones 0.6.1 y 0.6.2 requieren ese reemplazo inicial para corregir la identificación del plugin. Los repositorios privados requieren un token de solo lectura configurado en el sitio. Consulta [la configuración y publicación de actualizaciones](docs/updates.md).

## Autoría y licencia

Tourism Core es desarrollado por **Humble Labz**. Copyright (C) 2026 Humble Labz.

El código se distribuye bajo **GNU GPL v2 o posterior** (`GPL-2.0-or-later`); consulta [LICENSE](LICENSE) y [NOTICE](NOTICE). Esta licencia permite modificar y redistribuir el código conforme a sus condiciones, incluida la conservación de los avisos aplicables de copyright y licencia y la identificación de los cambios en los archivos modificados. La atribución original y la autoría de las modificaciones deben distinguirse. El historial del repositorio documenta los cambios; estos avisos no bloquean la edición de los archivos ni constituyen un registro de marca.

## Uso con WordPress o Astro

El plugin funciona con WordPress sin Astro. En **Tourism Core → Configuration → Enabled modules**, el interruptor **Integración con Astro / headless** está desactivado por defecto, también al actualizar una instalación que aún no tenga esta opción.

Actívalo y guarda la configuración solo si vas a conectar un frontend externo. Al activarlo se cargan la API de exportación, la API de contacto y la sección de mensajes de contacto con sus notificaciones. Esto no instala ni ejecuta Astro; el frontend y sus credenciales se configuran por separado. Al desactivarlo, esas rutas y la sección de mensajes dejan de registrarse; los mensajes y ajustes guardados se conservan. Los tours, shortcodes, reservas y pagos de WordPress siguen funcionando según sus propios ajustes.

## Entrega a Astro (0.6.0)

WordPress administra los datos; el frontend definitivo será Astro. El tema PHP y sus shortcodes actuales son la referencia local de contenido, no el frontend terminado. Ver [contrato de datos y conservación de URLs](docs/headless.md).

`GET /wp-json/tourism/v1/headless` entrega un contrato versionado para la construcción del sitio, con autenticación de administrador o la capacidad específica `tourism_export_headless`. Incluye contenidos publicados sin contraseña, campos públicos de tours, SEO, schema, traducciones, menús, taxonomías, rutas, aliases y el catálogo de archivos públicos conservados. No entrega opciones o metadatos arbitrarios, usuarios privados, reservas ni mensajes de contacto. Las credenciales solo pertenecen al servidor de construcción.

La entrega de Palotoa conserva `/tour/`, `/tour-category/` y `/tour-tag/` del origen. No cambia los permalinks ni el `noindex` del sitio local. La publicación requiere contrastar las rutas con el sitemap y Search Console del sitio público y ejecutar el rastreo sobre Astro.

## Preparación de migración (0.5.2)

Incluye videos de YouTube y reseñas propias de cada tour en **Videos y reseñas**. La fila vacía permite añadir un elemento al guardar. Las reseñas requieren aprobación explícita; una fila inválida conserva su valor anterior. No se guardan correos de clientes en estos campos públicos.

El tema puede usar `Tourism_Core_Tour_Extras::render_videos($post_id, 'main')`, `render_videos($post_id, 'sidebar')`, `render_sidebar_content($post_id)` y `render_reviews($post_id)`. Solo después de renderizar esos datos debe activar `tourism_core_tour_extras_visible`. Así el mismo conjunto de reseñas determina la valoración visible y `aggregateRating`, sin reutilizar contadores históricos inconsistentes. No se agregan opiniones generales de la agencia como si fueran opiniones de un tour.

Los videos conservan su URL original y tiempo de inicio. Se muestran mediante YouTube con privacidad mejorada. `VideoObject` solo se genera cuando se conocen título, miniatura y fecha original de publicación. No se inventan esos datos.

Los tours importados con `_tourism_preserve_language_values` conservan sus valores por idioma al guardarlos. Los demás tours mantienen la política anterior de sincronización. El importador separado trabaja por defecto en simulación y, al aplicarlo, crea borradores con claves de origen, comprobaciones de archivos e historial de importación. Las reservas antiguas permanecen en un archivo privado; los pagos nuevos siguen usando PayPal y WeTravel.

Prueba aislada: `php tests/tour-extras-test.php`. No sustituye las verificaciones de WordPress, del tema o de los proveedores de pago.

Referencias de marcado: [reseñas](https://developers.google.com/search/docs/appearance/structured-data/review-snippet) y [videos](https://developers.google.com/search/docs/appearance/structured-data/video).

Plugin de WordPress para contenido turístico y checkout externo. Versión 0.5.2.

## Diseños propios sin HTML del plugin (0.5.1)

`tourism_core_get_tours( array( 'category' => 'aventura', 'limit' => 6 ) )` devuelve una lista de datos de tours publicados, sin contraseña. Comparte los filtros y límites de `tourism_core_render_tours()`, pero permite que cada tema decida todo el HTML:

```php
foreach ( tourism_core_get_tours( array( 'category' => 'aventura', 'limit' => 6 ) ) as $tour ) {
    get_template_part( 'components/tour-card', null, $tour );
}
```

Los datos contienen `id`, `title`, `url`, `currency`, `price` (formateado), `duration`, `difficulty`, `group_size`, `max_elevation`, `excerpt`, `category`, `image_id` y `image_url`. Escapar los valores según su contexto. La imagen con ID se puede renderizar mediante `wp_get_attachment_image()`. No modifica el loop de WordPress y devuelve una lista vacía cuando no hay resultados o el módulo está desactivado.

## Secciones de tours en el frontend (0.5.0)

En `front-page.php` o cualquier plantilla del tema:

```php
if ( function_exists( 'tourism_core_render_tours' ) ) {
    echo tourism_core_render_tours( array(
        'category' => 'aventura',
        'limit' => 6,
        'columns' => 3,
        'fields' => array( 'image', 'title', 'duration', 'price', 'difficulty' ),
    ) );
}
```

En el contenido de una página, usar el bloque Shortcode:

```text
[tour_list category="aventura" limit="6" columns="3" fields="image,title,duration,price,difficulty"]
```

`category` utiliza el **slug** de Categorías de tours (`tour_category`), no una categoría de entradas. Se pueden indicar varios slugs separados por comas; basta pertenecer a uno. `tour_type` filtra Tipos de tour y se combina con la categoría. Omitir ambos muestra todos los tours publicados sin contraseña. Los campos disponibles son `image`, `title`, `duration`, `price`, `difficulty`, `group_size`, `max_elevation`, `excerpt` y `category`. Se omiten los datos vacíos; `excerpt` usa la introducción del tour. La imagen usa portada, imagen destacada o galería, en ese orden.

`limit` admite de 1 a 48 tours. El atributo anterior `posts_per_page` sigue funcionando; `limit` tiene prioridad si se especifican ambos. `columns` admite 1 a 4 y se adapta a tabletas y móviles. `orderby` admite `date`, `title`, `menu_order`, `modified` y `rand`; `order` admite `ASC` o `DESC`. Las listas vacías no generan un contenedor. La función devuelve HTML y conserva el loop de la página; las consultas no calculan paginación.

Para cambiar el diseño, copiar `templates/tour-card.php` a `tourism-core/tour-card.php` dentro del tema activo. La plantilla recibe `$post_id`, `$card` y `$fields`. Los overrides existentes siguen recibiendo `$post_id`, pero deben adoptar `$fields` para respetar la selección de detalles. Para construir HTML propio, `tourism_core_get_tour_card_data( $id )` devuelve los datos del tour; escapar cada valor según su contexto. No se ha agregado todavía un bloque visual de Gutenberg ni modificado la portada del tema.

Validación local: `php -c <php.ini-del-sitio> tests/wordpress-tour-list-test.php`. Crea tours y términos temporales, comprueba filtros, privacidad, selección de campos, precios, compatibilidad y conservación del loop, y elimina sus datos al terminar.

## Configuración de pagos

1. Abrir **Tourism Core → Payments**. WeTravel y PayPal se configuran juntos, sin selector.
2. Configurar únicamente el ID real y verificado de WeTravel. No existe un ID predeterminado; sin él, este proveedor permanece desactivado.
3. En cada tour, completar únicamente su UUID de WeTravel, si se desea ofrecer ese método.
4. Para PayPal, configurar un único correo receptor y la moneda (USD o EUR) en Payments. No hay correo PayPal por tour. Configurar el número de WhatsApp con código de país en los datos de la agencia.
5. Insertar el botón en una página o plantilla.

```text
[tour_payment_button]
[tour_payment_button post_id="123" label="Reservar ahora"]
```

En una plantilla del tema:

```php
echo Tourism_Core_Payment_Providers::render_button(
    array( 'post_id' => get_the_ID(), 'label' => 'Reservar ahora' )
);
```

Los temas que llaman directamente a `tourism_core_render_wetravel_checkout_button()` siguen funcionando. Para aplicar la disponibilidad automática de métodos y la alternativa WhatsApp, deben usar el renderizador común anterior. La plantilla `single-tour.php` del tema travel-theme de este proyecto ya utiliza este renderizador; conserva su bloque anterior como alternativa si el plugin está desactivado.

El checkout PayPal se construye con el correo general, el nombre e ID del tour, su precio actual y la moneda configurada. Requiere un precio positivo. No convierte monedas: el precio del tour debe estar en la moneda elegida. Un correo inválido conserva el anterior y muestra un aviso al guardar.

Se muestran automáticamente todos los métodos completos: ambos botones si ambos están configurados; solo WeTravel si existe cuenta y UUID de ese tour; solo PayPal si existe correo y precio válido. Dejar el correo o la cuenta vacíos desactiva el método correspondiente. Si ninguno está disponible, aparece WhatsApp con el número de la agencia y el nombre del tour. Si falta ese número, no se genera un enlace roto y Payments muestra un aviso para configurarlo.

Los selectores, correos y enlaces por tour de las primeras entregas ya no se usan. Los datos antiguos se conservan en la base de datos, pero no pueden activar PayPal cuando su correo general está vacío.

Esta entrega utiliza checkout externo: no crea transacciones PayPal por API, no verifica pagos, no sincroniza reservas ni envía notificaciones de pago. El módulo de solicitudes de reserva existente continúa siendo independiente.

## Extensión de proveedores

El filtro `tourism_core_payment_providers` expone un registro con claves de proveedor y propiedades `label` y `render` (callback). El callback recibe `post_id`, `label`, `class` y `echo=false`, y debe devolver HTML con sus valores escapados. Un proveedor adicional debe implementar también su configuración, validación y diagnóstico. Eazy Pay todavía no está implementado.

## Validación

Ejecutar `php tests/regression-test.php`. Las pruebas comprueban las cuatro combinaciones de métodos, la alternativa WhatsApp, los datos de checkout, la conservación de ajustes, la configuración sin selectores, búsqueda de destinos y ausencia de recursión en estados de reservas. No sustituyen una prueba del checkout real con cada proveedor.

Para probar las reservas con WordPress activo, ejecutar `php -c <php.ini-del-sitio> tests/wordpress-bookings-test.php` desde la carpeta del plugin. Esta prueba crea tours, reservas y un usuario temporal, simula el transporte de correo para no enviar mensajes reales y elimina los datos de prueba al terminar. Usar únicamente en un sitio local de desarrollo. Se comprueban validación, bloqueos de concurrencia, duplicados, límites, permisos y reintentos de correo contra la base de datos real.

## Solicitudes de reserva

- Solo se aceptan tours publicados. La fecha sigue siendo opcional; si se indica, debe ser una fecha real desde hoy, según la zona horaria de WordPress.
- La cantidad debe ser un entero entre 1 y 100. El máximo puede ajustarse mediante `tourism_core_booking_max_people`.
- Nombre, correo, teléfono y mensaje tienen límites de longitud. Los mensajes conservan sus saltos de línea.
- Un campo trampa rechaza envíos automatizados. Se permiten hasta 3 solicitudes distintas por correo y 5 por IP en diez minutos. Las claves almacenadas usan hashes; no se almacena la IP sin procesar para estos controles.
- Repetir exactamente una solicitud dentro de diez minutos reutiliza la reserva existente y no reenvía correos. Los bloqueos en la base de datos impiden crear duplicados simultáneos.
- Una reserva guardada es una solicitud, no una confirmación de cupo ni de pago.

## Permisos y correos

Las reservas requieren `manage_tourism_bookings`, que se asigna a administradores al actualizar. Editar tours no concede acceso a datos de clientes. Puede asignarse expresamente esta capacidad a un rol de operaciones. La creación manual de reservas vacías desde el administrador está deshabilitada; el formulario crea solicitudes completas.

Cada reserva muestra el estado del aviso a la agencia y al cliente, el último error y el número de intentos. Un correo aceptado por `wp_mail` se presenta como aceptado por el servicio, no como entrega confirmada. Ante un fallo se programan hasta tres reintentos, con esperas de 1, 5 y 15 minutos. Estos trabajos usan WP-Cron y dependen de su ejecución en el sitio. Un administrador puede reiniciar una notificación fallida desde la reserva; una notificación aceptada no se reenvía automáticamente. Desactivar el plugin elimina sus tareas programadas.

El editor de tours tiene una sola caja **Qué llevar**, con una lista de equipaje con iconos y recomendaciones detalladas. Ambos formatos y sus datos anteriores se conservan.

## Próximas entregas

- Definir salidas, pasajeros, reservas y transacciones como conceptos separados.
- Implementar un flujo completo de reserva y confirmación de pago con validación del proveedor.
- Añadir disponibilidad, automatizaciones y estructura de distribución gratuita/Pro.

## Datos estructurados de tours

Cada página pública de un tour incluye un único bloque JSON-LD con una entidad de tipos `TouristTrip` y `Product`, una oferta cuando existe un precio válido y la agencia como `TravelAgency` cuando está configurada. Cuando no hay oferta ni reseñas publicables se emite solo `TouristTrip`, sin campos exclusivos de producto. No se publica en previews, feeds, borradores ni tours protegidos por contraseña. Si falta la introducción, la descripción resume el texto del itinerario que se muestra en esa página, sin modificar el contenido guardado.

Los datos salen de los metaboxes existentes:

| Datos del tour | Marcado |
| --- | --- |
| Título, introducción, portada y galería | `name`, `description`, `image` |
| Precio actual | `Offer.price` |
| Tipo y categoría de tour | `touristType`, `category` |
| Duración, dificultad, grupo y altitud | `Product.additionalProperty` |
| Destinos públicos relacionados | `itinerary` con lugares sin suponer un orden cronológico |
| Días del itinerario y sus características | `subTrip` con etapas vinculadas mediante `partOfTrip` |
| Incluidos, excluidos, destacados, recomendaciones, detalles de precio y preguntas | `PropertyValue` dentro de `additionalProperty` |
| Datos generales de la agencia | `TravelAgency`, `provider`, `seller` y marca |

La nueva caja **Datos estructurados del tour** añade moneda (USD/EUR/PEN o heredar la general), disponibilidad declarada, vigencia del precio, SKU y lugar de salida. No exige rellenar datos opcionales ni inventa disponibilidad, reseñas, estrellas o códigos comerciales. Su diagnóstico señala datos básicos incompletos. La disponibilidad declarada no sincroniza cupos ni bloquea por sí sola los checkouts externos.

Moneda y precio son compartidos con PayPal: una moneda PEN se identifica como PEN y no se cobra como USD/EUR mediante una conversión implícita. El precio cero explícito es válido para Schema.org; un precio vacío, negativo, no numérico o vencido no genera `Offer`. Sin oferta, el tipo `Product` se conserva únicamente si hay reseñas publicables o una valoración agregada. No se inventan precios, disponibilidad ni fechas de vigencia.

**Tours → Revisión de schema** muestra los tours publicados de todos los idiomas, sus precios, valoraciones y datos pendientes, con enlaces para editarlos. También avisa si WordPress mantiene la opción de no indexar y enlaza a Rich Results Test. En Local hay que usar la pestaña de código de esa herramienta; en producción, revisar las URLs definitivas, acceso para Google, canonicals, sitemap y Search Console. No añadir envíos o devoluciones de bienes físicos a estos tours para eliminar avisos de Shopping.

`php -c <php.ini-del-sitio> tests/schema-readiness-test.php` comprueba sin escribir datos el resumen del itinerario, la emisión condicional de Product y la coherencia de ofertas/reseñas. Las pruebas locales no sustituyen Rich Results Test ni una revisión de indexación en producción.

Los campos adicionales tienen un renderizador público `Tourism_Core_Tour_Schema::public_details($post_id)` para mostrar SKU, salida, disponibilidad y vigencia junto al precio. Los temas deben usar también `tourism_core_get_tour_currency($post_id)` para mostrar la misma moneda del marcado. La plantilla `single-tour.php` de travel-theme ya incorpora ambos y conserva los decimales del precio.

Si otro plugin genera el mismo marcado, se puede desactivar este bloque por tour o mediante el filtro `tourism_core_tour_schema_enabled`. El filtro `tourism_core_tour_schema_graph` permite integrarlo con un grafo externo. No se desactivan automáticamente los esquemas generales de otros plugins.

Pruebas locales: `php -c <php.ini-del-sitio> tests/wordpress-schema-test.php` crea y elimina datos temporales; `node tests/schema-http-check.cjs` comprueba que un tour público real devuelve un solo bloque válido con los dos tipos. Los tests de JSON y mapeo no equivalen a una certificación de Google ni garantizan resultados enriquecidos.

Referencias: [Trip y propiedades de itinerario y etapas](https://schema.org/Trip), [TouristTrip](https://schema.org/TouristTrip), [Product](https://schema.org/Product), [requisitos de Google para resultados de producto](https://developers.google.com/search/docs/appearance/structured-data/product-snippet).

## Editor de tours (0.4.0)

El formulario conserva el guardado, permisos y datos existentes de WordPress. Con JavaScript se organiza en siete secciones: Información general, Fotos, Experiencia, Itinerario, Precio y reservas, Preguntas frecuentes y Avanzado. Los metaboxes de otros plugins permanecen disponibles fuera de estas secciones; no se modifica su marcado SEO. Sin JavaScript se conserva el formulario original.

Desde 0.4.1 se activa un estado de preparación antes de pintar las cajas originales, evitando que aparezcan brevemente antes del editor por secciones. La preparación termina al completar la organización del formulario. Si el archivo del editor falla o pasan diez segundos sin inicializar, se recupera el formulario original con un aviso y no se intenta cambiarlo después en esa carga.

Con la misma preparación local de las pruebas del editor, `node tests/tour-editor-loading-check.cjs` verifica los primeros fotogramas con el archivo retrasado, la recuperación ante errores de carga y el acceso sin JavaScript.

- Moneda, disponibilidad y vigencia están junto al precio; el lugar de salida está en información general. Se trasladan los campos originales sin duplicarlos ni cambiar sus claves.
- La navegación admite flechas, Inicio y Fin y recuerda la sección durante la sesión para cada tour. Los botones Anterior/Siguiente no envían el formulario.
- Las listas de opciones se muestran bajo Personalizar opciones. Los destinos publicados se pueden buscar sin desmarcar selecciones.
- La lista de datos básicos muestra sugerencias para nombre, introducción, imagen y precio; no impide publicar ni declara completo el SEO.
- El resumen de reservas usa la configuración general y los valores editados para indicar WeTravel, PayPal o WhatsApp. No añade un correo PayPal por tour ni confirma pagos o cupos.
- El itinerario ofrece títulos visibles, desplegables, duplicación y botones para mover días. Antes de duplicar o mover se sincronizan los editores para conservar el texto y los detalles anidados.
- Preguntas, destacados y otras listas de contenido se pueden contraer y desplegar. Los campos de secciones ocultas siguen enviándose al guardar.
- Los errores de validación HTML abren la sección correspondiente y se muestran junto al campo. Las etiquetas propias del editor se presentan en español; los controles nativos de WordPress usan el idioma del sitio.

Prueba de navegador local: configurar `TOURISM_EDITOR_STATE` con una ruta temporal, preparar con `php -c <php.ini-del-sitio> tests/tour-editor-fixture.php prepare`, ejecutar `node tests/tour-editor-ui-check.cjs`, verificar lo guardado con la operación `verify` y terminar siempre con `cleanup`. Requiere `playwright-core` (o `TOURISM_PLAYWRIGHT` con su ruta) y Chrome (`TOURISM_CHROME` permite otra ruta). La prueba crea un administrador y un tour temporales solo en un entorno WordPress `local`; las cookies quedan en el archivo temporal y se eliminan con la limpieza. `TOURISM_EDITOR_SCREENSHOTS` permite guardar capturas fuera del repositorio.

## Editores de Experiencia (0.4.2)

Destacados, Qué esperar, Incluidos y Excluidos usan el editor completo de WordPress, con la misma configuración de Recomendaciones detalladas: ocho filas iniciales, herramientas completas, Visual/Code y Añadir medios. Incluidos y Excluidos se muestran en bloques verticales para dejar espacio a las herramientas y las imágenes. El cambio se limita a la sección Experiencia.

Los campos existentes se crean una vez mediante `wp_editor()` y su inicialización nativa. JavaScript inicializa solamente los campos nuevos o los que se reconstruyen al reordenar, evitando los controles Visual/Code duplicados. Las cuatro listas comparten una plantilla y un controlador; conservan las claves de sus metadatos. Al eliminar o arrastrar se guardan los editores, se reconstruyen los identificadores y las asociaciones de Añadir medios y se inicializa un solo editor por campo.

Prueba específica con una preparación local nueva: `node tests/tour-experience-ui-check.cjs` verifica controles únicos al cargar, añadir, eliminar, arrastrar y recargar; abre la biblioteca nativa para insertar una imagen en cada lista; comprueba el texto en la pestaña Code y el ancho móvil. Después usar `php -c <php.ini-del-sitio> tests/tour-editor-fixture.php verify-experience` para comprobar los metadatos guardados y terminar con `cleanup`. La imagen temporal referencia un icono del plugin; no se copian archivos a uploads y la entrada de medios se elimina durante la limpieza.

## Editor completo en todas las secciones (0.4.3)

Introducción, descripciones del itinerario, respuestas de preguntas frecuentes y detalles del precio también usan el editor completo con Añadir medios. La configuración compartida `TourismCoreRichEditorSettings()` toma las barras y plugins del editor nativo de Recomendaciones detalladas y los aplica a los campos dinámicos, incluyendo los de Experiencia. De esta forma no dependen de la barra reducida que devuelve por defecto la API de inicialización dinámica de WordPress.

Las listas guardan y retiran los editores antes de arrastrar sus elementos y los reconstruyen al terminar, conservando el contenido y actualizando el editor asociado al botón de medios. Los archivos fuente y las variantes de distribución se mantienen sincronizados. `node tests/tour-all-editors-check.cjs` comprueba herramientas completas, un solo par Visual/Code y un botón de medios por editor, además de añadir/eliminar campos y abrir la biblioteca con el editor correcto en itinerario, preguntas y precio. Usa la misma preparación y limpieza local de las pruebas anteriores.
