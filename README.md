# Tourism Core

Plugin de WordPress para contenido turístico y checkout externo. Versión 0.5.0.

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
2. Verificar el ID de cuenta de WeTravel. Para conservar las instalaciones existentes, el valor anterior (`1685394`) sigue siendo el predeterminado hasta cambiarlo.
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

Cada página pública de un tour incluye un único bloque JSON-LD con una entidad de tipos `TouristTrip` y `Product`, una oferta cuando existe un precio válido y la agencia como `TravelAgency` cuando está configurada. No se publica en previews, feeds, borradores ni tours protegidos por contraseña.

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

Moneda y precio son compartidos con PayPal: una moneda PEN se identifica como PEN y no se cobra como USD/EUR mediante una conversión implícita. El precio cero explícito es válido para Schema.org; un precio vacío, negativo, no numérico o vencido no genera `Offer`. Sin oferta, el tour sigue teniendo ambos tipos, pero no reúne por esa vía los requisitos de un resultado enriquecido de producto.

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
