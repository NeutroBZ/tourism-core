# Actualizaciones desde GitHub

Desde 0.6.1, Tourism Core consulta las GitHub Releases estables de
`NeutroBZ/tourism-core`. No instala código de `main` ni versiones preliminares.
La consulta se realiza durante las comprobaciones de actualizaciones de WordPress;
se conserva en caché una hora (cinco minutos si falla).

## Preparar cada sitio una sola vez

Instala el ZIP `tourism-core.zip` de la release 0.6.1 o posterior reemplazando
el plugin existente. Las versiones anteriores no incluyen el actualizador.
La carpeta instalada debe ser `tourism-core`.

Si el repositorio es privado, crea un fine-grained personal access token de GitHub
limitado a este repositorio con **Contents: Read-only**. En el `wp-config.php`
de cada sitio, antes de cargar WordPress, añade:

```php
define( 'TOURISM_CORE_GITHUB_TOKEN', 'TU_TOKEN_DE_SOLO_LECTURA' );
```

No incluyas el token en el plugin, Git, ZIPs ni URLs. Renuévalo si caduca o se
revoca. Un repositorio público no necesita token. No uses un token con permisos
de escritura en los sitios de clientes.

Ve a **Escritorio → Actualizaciones → Comprobar de nuevo**. Las nuevas versiones
aparecerán con **Actualizar ahora**. También puedes habilitar las actualizaciones
automáticas desde **Plugins**; el plugin respeta la elección de WordPress y no
las fuerza. WordPress necesita conectividad con GitHub, permisos para escribir
en los plugins y WP-Cron operativo para sus actualizaciones automáticas.

Los ajustes y contenidos se conservan al actualizar. Las modificaciones hechas
directamente en los archivos del plugin serán reemplazadas.

## Publicar una versión para todos los proyectos

1. Actualiza la versión en `tourism-core.php`, `config/constants.php` y `readme.txt`.
2. Ejecuta las pruebas y sube el commit a GitHub.
3. Crea una etiqueta nueva, por ejemplo `v0.6.2`, y súbela sin reemplazar etiquetas anteriores.
4. GitHub Actions valida la versión y publica una Release con `tourism-core.zip`.

El ZIP contiene la carpeta `tourism-core/` y excluye pruebas y herramientas.
Solo una Release estable con ese archivo se ofrece a los sitios.
GitHub Actions debe estar habilitado y disponer del permiso de escritura de
contenidos declarado en el workflow. Si falla, revisa la ejecución en Actions.

El token se envía únicamente a la API del repositorio para consultar o descargar
la release. Las redirecciones de descarga se solicitan sin el token.
