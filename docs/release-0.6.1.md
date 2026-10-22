# Tourism Core 0.6.1

- Actualizador nativo de WordPress conectado a GitHub Releases estables.
- Descarga del archivo instalable `tourism-core.zip` con carpeta `tourism-core/`.
- Repositorios privados mediante `TOURISM_CORE_GITHUB_TOKEN` de solo lectura.
- Integración con Astro / headless opcional, apagada por defecto.
- Avisos de autoría de Humble Labz y licencia GPL v2 o posterior.

## Comprobaciones locales

- 104 archivos PHP sin errores de sintaxis.
- 74 comprobaciones aisladas del actualizador: PASS (37 públicas, 37 privadas).
- 24 comprobaciones de regresión: PASS.
- 46 comprobaciones de integración del interruptor headless: PASS.

Las pruebas del actualizador simulan GitHub, sus errores y redirecciones; no
instalan una actualización en los sitios de clientes. PHP Local conserva el
aviso previo de Imagick ausente.

## Publicación

Sincronizar `main` y `develop`, crear la etiqueta nueva `v0.6.1` y subirla.
El workflow `.github/workflows/release.yml` valida la versión y publica el ZIP.
No reemplazar `v0.6.0` ni otras etiquetas existentes.

Los sitios con 0.6.0 necesitan una instalación inicial de 0.6.1 para incorporar
el actualizador. El repositorio es público: no se necesita token. Si se cambia
a privado, configurar el acceso según [updates.md](updates.md). Activar las actualizaciones automáticas
en WordPress si se desean instalaciones sin pulsar «Actualizar ahora».
