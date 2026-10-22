# WordPress → Astro: contrato y conservación de SEO

El frontend definitivo es Astro en Netlify. WordPress y Tourism Core administran contenido y datos. Las plantillas PHP actuales no constituyen el frontend Astro terminado. No borrar contenido del editor ni ejecutar el importador antiguo: el contenido fuente se conserva para construir los componentes sin perder información.

## Obtener los datos

`GET /wp-json/tourism/v1/headless` devuelve el contrato v1, con `Cache-Control: private, no-store`. Requiere un administrador o un usuario dedicado con la capacidad `tourism_export_headless`. Para producción, usar una contraseña de aplicación de WordPress sobre HTTPS y un usuario dedicado con esa capacidad y `read`. No hace falta entregar credenciales de administrador a Astro.

Variables privadas del servidor de construcción:

```text
WP_BACKEND_URL=https://<dominio-permanente-del-backend>
WP_BUILD_USER=<usuario-de-construccion>
WP_BUILD_APP_PASSWORD=<contraseña-de-aplicacion>
FRONTEND_ORIGIN=https://palotoaamazontravel.com
```

Ninguna credencial lleva el prefijo `PUBLIC_`, se envía al navegador o se incluye en el repositorio. `tools/headless-client.mjs` permite cargar el contrato en el servidor. Consultar WordPress una sola vez por construcción y compartir el resultado entre páginas.

Para trabajar en Local sin publicar ni crear credenciales, exportar a una carpeta privada existente fuera del directorio público:

```text
php -c <php.ini-del-sitio> tools/export-headless.php <directorio-privado>
```

Definir `WP_SNAPSHOT_FILE` con la ruta privada de `snapshot.json` para cargarlo mediante el mismo cliente. No copiar el snapshot entero a `public/` ni usar un fetch del navegador para descargarlo. La prueba local con todos los datos y archivos se ejecutó con un límite de PHP de 256 MB; no asumir que un servidor con límites inferiores puede producirlo.

## Datos del contrato

| Campo | Uso por Astro |
| --- | --- |
| `posts` | Páginas, entradas, tours y destinos publicados, sin contraseña |
| `content.html`, `source_sha256`, `public_sha256` | Texto fuente preservado y comprobación de integridad; solo se normalizan URLs en la entrega |
| `content.segments` | HTML editorial y declaraciones de componentes, sin ejecutar shortcodes |
| `fields` | Campos públicos estructurados del tour, itinerario, galería, precios, videos, mapas y reseñas aprobadas |
| `seo` | Título resuelto, descripción, canonical, reglas de robots, Open Graph, Twitter, datos originales de Yoast y su grafo público |
| `seo.primary_terms`, `seo.breadcrumbs` | Categorías principales con IDs actuales y enlaces de las migas de pan; no interpretar los IDs históricos como IDs del frontend |
| `tour_schema` | Grafo del tour, con Product/Offer/Review cuando corresponden a sus datos reales |
| `article_schema` | Datos editoriales de entradas y autores originales, independientes del noindex del backend; respeta la desactivación explícita de Article |
| `translations` | IDs, URLs y etiquetas hreflang recíprocas |
| `author` | Nombre y URL del autor público original; no contiene usuarios, emails ni credenciales |
| `menus` | Nueve menús, con jerarquía, orden, destino, target y rel |
| `terms` | Taxonomías, jerarquía, idiomas y datos SEO recuperados |
| `routes` | Rutas que Astro debe servir, incluyendo archivos, paginación, feeds, robots y sitemaps |
| `redirects`, `route_aliases` | Redirecciones heredadas y aliases, sin sustituir las URLs originales por las provisionales |
| `legacy_post_ids`, `legacy_media_ids`, `query_routes` | Resolución de enlaces antiguos con `p`, `page_id`, `attachment_id`, búsqueda y paginación |
| `media` | Metadatos públicos de medios, tamaños, alt y captions |
| `assets` | Catálogo de archivos públicos conservados, con ruta, bytes y SHA-256 |
| `site.public_integrations` | Identificadores públicos de seguimiento recuperados; no ejecuta scripts |
| `site.external_reviews` | Google y Tripadvisor desde los plugins Trustindex activos: estado, nombre del negocio y reseñas públicas de agencia; excluye reseñas ocultas y respeta filtros de estrellas/texto |

`backend_url`, `backend_origin`, las URLs de origen de medios y las acciones del backend son referencias técnicas. No usarlas como canonical ni como enlaces de navegación del frontend.

No se exportan reservas, pedidos, solicitudes de contacto, contraseñas, tablas completas ni opciones/metadatos arbitrarios. Los datos de revisores se limitan a lo ya publicado: autor, contenido, puntuación y fecha válida.

## Política de rutas de Palotoa

Conservar `https://palotoaamazontravel.com` y las rutas originales `/tour/`, `/fr/tour/`, `/tour-category/` y `/tour-tag/`. Las bases de taxonomía se verificaron contra las reglas de WordPress guardadas en el respaldo original. Los aliases `/tours/`, `/tour_category/` y `/tour_tag/` corresponden a la instalación intermedia, no deben convertirse en los nuevos canonicals de los tours originales.

Conservar páginas, entradas, categorías, etiquetas, autores, archivos por fecha y paginación que recoge el inventario. Los IDs antiguos de enlaces no son los IDs nuevos de WordPress: usar los mapas correspondientes. Las redirecciones heredadas tienen prioridad sobre archivos estáticos que pudieran ocultarlas. No redirigir páginas ausentes a la portada para ocultar errores.

Mantener `/sitemap_index.xml` y los sitemaps de grupos que ya utilizaba Yoast. El sitemap automático de Astro puede usar otros nombres: implementar los nombres originales o redirecciones comprobadas. Exportar solo URLs canónicas e indexables en el sitemap definitivo; no incluir previews, páginas vacías o archivos con noindex por política original.

## Componentes que debe construir el frontend

El diseño se implementa en componentes Astro. Las declaraciones preservadas son datos para reconstruir su comportamiento, no instrucciones para colocar shortcodes en HTML público:

- `tourism_video`: video y poster originales.
- `tour_list`: selección por categorías/tipos, límite, orden y campos. Cada tour ya incluye sus términos y orden.
- `tourism_selection`: selección por IDs del origen; resolverlos con `legacy_post_ids`.
- `tourism_blog`: listado de entradas y paginación, actualmente 21 por página.
- `tourism_contact`: formulario conectado al backend en tiempo de ejecución mediante el endpoint autenticado descrito debajo. No reutilizar un nonce generado durante una construcción estática.
- `trustindex`: integración externa de reseñas; conservar la declaración de proveedor y resolver su presentación sin inventar reseñas ni mezclar valoraciones de la agencia con las de un tour.

Para `trustindex`, resolver `attributes['no-registration']` contra `site.external_reviews.google` o `.tripadvisor`. Cada entrada tiene `status` (`plugin_inactive`, `connection_required`, `no_reviews`, `ready`), `label`, `business_name`, `scope: agency` e `items` con `author`, `content`, `rating` (nullable, escala 1–5) y `date` (nullable, ISO). Renderizar texto escapado, sin ejecutar el HTML ni las reseñas de demostración del plugin. No añadir estos datos al schema de los tours. Vincular primero el perfil real en cada panel de Trustindex; después descargar las reseñas y regenerar el snapshot/reconstruir Astro. La conexión no consulta servicios externos durante la exportación. En local solo se permiten las solicitudes HTTPS de WordPress a `admin.trustindex.io` y `cdn.trustindex.io` además de los hosts locales; otras restricciones de red del entorno siguen aplicando.

El resumen visual del widget usa `rating` y `total_reviews` del proveedor, ambos nullable, con `rating_scale: 5`. `displayed_reviews` cuenta solo las tarjetas exportadas: nunca sustituye al total oficial. `profile_url` enlaza al negocio, `icon_url` al icono del proveedor servido por el CDN que usa el plugin y `last_synced_at` a la fecha disponible en sus datos. Cada reseña incluye `avatar_url` HTTPS cuando existe. El nombre del negocio procede de `getPageDetails()` para normalizar los nombres guardados como JSON. Astro debe representar la valoración con estrellas fraccionarias en Google y círculos en Tripadvisor, incluir texto accesible y renderizar fechas/contadores según EN/FR. Los iconos y avatares externos requieren conectividad del navegador o una estrategia posterior de copia/caché validada.
- `woocommerce_my_account`: página antigua `/my-account/`. Su shortcode no es un portal funcional en Astro. Confirmar si los clientes siguen usando cuentas antes de retirarlo o sustituir su funcionalidad. No borrar la URL ni exponer los pedidos archivados.

El HTML editorial del blog puede renderizarse con un tratamiento seguro. La maquetación de las páginas estructurales se reconstruye en código conservando sus textos, enlaces e imágenes. Mantener IDs de anclas referenciados por enlaces. No insertar scripts del contenido fuente indiscriminadamente.

## Formulario independiente del tema

`POST /wp-json/tourism/v1/headless/contact` recibe `name`, `email`, `subject`, `message` y `fingerprint` desde un proxy de servidor de Netlify. Requiere autenticación HTTPS y la capacidad `tourism_submit_contact` o administración. Usar una credencial de ejecución separada de la credencial de lectura del build; no entregarla al navegador.

El proxy debe validar entrada, honeypot/captcha cuando proceda y generar `fingerprint` como HMAC-SHA256 hexadecimal de la IP real proporcionada por la plataforma y un secreto de servidor. No aceptar una IP o fingerprint propuestos por el navegador ni reenviar encabezados arbitrarios como identidad. WordPress limita a una solicitud por minuto por fingerprint. Así se evita que todos los visitantes compartan el límite de la IP del proxy.

La respuesta válida es `202 {"accepted":true}`. El mensaje se guarda privado y se notifica mediante el servicio de correo existente; si la notificación falla el mensaje no se pierde. La aceptación no equivale a confirmación de entrega SMTP. Entrada inválida devuelve 400, repetición 429 y falta de permiso 401/403. No hay colección pública de solicitudes ni se exponen sus IDs. La prueba local `tests/headless-contact-test.php` intercepta el correo y elimina los mensajes sintéticos.

Antes de emitir `tour_schema`, mostrar las mismas reseñas, precios, imágenes y datos que lo respaldan. Serializar JSON-LD de forma segura para que contenido como `</script>` no cierre la etiqueta. No volver a generar un segundo Product desde otra integración. Combinar los grafos por `@id`; no duplicar las entidades de Website/Organization/Person. No añadir disponibilidad, fechas, códigos, envíos o devoluciones ficticias para eliminar avisos de Google.

Para entradas, usar `article_schema` junto a los metadatos editoriales y mostrar el autor y fechas correspondientes. El grafo generado por Yoast en un backend noindex puede omitir Article: no usar esa omisión para perder el marcado editorial en el frontend. Deduplicar el Article si otra integración ya lo devuelve.

## Medios y backend permanente

El catálogo conserva URLs de archivos que no aparecen en los artículos actuales pero que podrían seguir enlazados o indexados. Mantener `/wp-content/uploads/...` accesible mediante copia o proxy comprobado hacia el backend HTTPS permanente. No subir los respaldos privados al frontend. No hace falta incluir decenas de miles de variantes de imágenes en cada despliegue: la estrategia de distribución puede conservarlas en el backend, manteniendo accesibles sus URLs públicas.

El backend necesitará una dirección permanente accesible desde Netlify; el dominio `.local` no lo es. Sus páginas administrativas/editoriales pueden permanecer sin indexar, pero no bloquear de forma global los medios públicos que deban seguir rastreables. Comprobar respuesta, Content-Type, tamaño y hash de archivos, y también imágenes `srcset`, videos y posters. Vigilar el consumo de transferencia del alojamiento elegido.

## Condiciones para publicar

1. Contrastar el inventario con sitemap, URLs enlazadas y páginas de Search Console del sitio público actual. El inventario local y los respaldos no prueban por sí solos cobertura completa del sitio público en el momento del cambio.
2. Construir cada ruta o su redirección y probar búsqueda, paginación, selector de idioma, formularios, pagos externos y la decisión de `/my-account/`.
3. Comparar el rastreo de Astro con `url-map.csv`, `internal-links.json`, el catálogo de archivos y los metadatos exportados. Cero rutas o medios perdidos y cero bucles de redirección.
4. Comprobar HTML servido, encabezados, H1, canonical, hreflang recíproco, OG/Twitter, robots y sitemaps. Conservar verificaciones y seguimiento existentes.
5. Probar código/URLs de Astro en Rich Results Test. Las validaciones PHP actuales no certifican el HTML que Astro todavía no genera.
6. Mantener previews sin indexar. Aprobar el cambio al hosting nuevo antes de modificar DNS o abrir indexación. El indicador `source_noindex` del backend local no es una instrucción para desindexar el frontend definitivo.
7. Comprobar las URLs y redirecciones otra vez con el dominio definitivo y monitorizar Search Console. No se puede garantizar que una migración no produzca fluctuaciones de posicionamiento.

Pruebas disponibles: `tests/headless-contract-test.php` (datos, permisos e integridad, sin escrituras de prueba) y `tests/validate-headless-links.cjs snapshot.json` (rutas, enlaces, archivos declarados y redirecciones). Los archivos generados permanecen fuera del directorio público.

Referencias: [Google: migración y mapas de URLs](https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes), [WordPress: endpoints REST](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/).
