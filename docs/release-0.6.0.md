# Publicar Tourism Core 0.6.0

`main` identifica la version estable; `develop` conserva el desarrollo. La etiqueta
`v0.6.0` identifica esta entrega. No incluye Palotoa Live Reviews, que es otro plugin.

Validacion local del 22 de octubre de 2026:

- 102 archivos PHP sin errores de sintaxis.
- 24 comprobaciones de regresion: PASS.
- Prueba aislada de resenas: PASS.
- 9 comprobaciones de schema: PASS.
- 15 comprobaciones de contacto: PASS; correo interceptado y fixture eliminado.
- 21173 comprobaciones del contrato headless: PASS.

PHP Local muestra un aviso de extension Imagick ausente; no impidio estas pruebas.
Tripadvisor sigue sin datos y su URL de perfil almacenada esta mal formada;
queda pendiente corregir la configuracion del proveedor. No se certifica aqui
equivalencia visual de Astro ni una auditoria completa de seguridad.

## Publicacion desde PowerShell con acceso a GitHub

Ejecutar dentro de la carpeta del plugin. Cada comprobacion detiene la publicacion
si GitHub tiene cambios que no estan integrados. No usar force push.

```powershell
git fetch origin
if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar GitHub' }
git merge-base --is-ancestor origin/main main
if ($LASTEXITCODE -ne 0) { throw 'Revisar e integrar los cambios nuevos de origin/main' }
git merge-base --is-ancestor origin/develop develop
if ($LASTEXITCODE -ne 0) { throw 'Revisar e integrar los cambios nuevos de origin/develop' }
$releaseCommit = git rev-parse 'v0.6.0^{commit}'
$mainCommit = git rev-parse main
$developCommit = git rev-parse develop
if ($releaseCommit -ne $mainCommit -or $releaseCommit -ne $developCommit) {
    throw 'Las ramas y la etiqueta ya no coinciden; revisar antes de publicar'
}
git push --atomic origin main develop refs/tags/v0.6.0
if ($LASTEXITCODE -ne 0) { throw 'Publicacion incompleta; revisar el error de Git' }
```

Despues, establecer `main` como rama predeterminada en GitHub, en Settings >
Default branch. El remoto apuntaba a `develop` en la ultima referencia local.
La etiqueta no crea automaticamente una GitHub Release.
