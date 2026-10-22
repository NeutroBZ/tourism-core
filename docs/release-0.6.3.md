# Tourism Core 0.6.3

Corrige `TOURISM_CORE_FILE`: la ruta debe apuntar directamente al archivo
principal, sin segmentos `..`. WordPress no los resuelve en `plugin_basename()`;
la comparación de la versión anterior no coincidía con `tourism-core/tourism-core.php`
y no ofrecía actualizaciones.

Incluye las mejoras de videos y reseñas de 0.6.2. Los sitios con 0.6.1 o 0.6.2
necesitan reemplazar el plugin una vez por 0.6.3 o posterior para incorporar
esta corrección. Las etiquetas anteriores se conservan sin modificarlas.

La regresión `tests/wordpress-updater-identity-test.php` usa el identificador
real de WordPress y comprueba el filtro registrado por el plugin con una
respuesta GitHub simulada; no depende del stub aislado de `plugin_basename()`.
