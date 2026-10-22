# Tourism Core 0.6.2

Videos y reseñas se integra como la octava sección del editor de tours.
Ambas listas permiten añadir y eliminar filas sin guardar entre elementos;
los cambios se aplican al guardar el tour. Los índices originales se conservan
durante la edición para no cambiar la identidad de reseñas importadas ni
recuperar el elemento incorrecto si otra fila es inválida.

Las reseñas nuevas siguen sin aprobarse por defecto. Se pueden eliminar todos
los elementos. Sin JavaScript quedan disponibles la fila vacía y las casillas
de eliminación. Los videos siguen usando las URLs de YouTube y su ubicación
principal o lateral. No cambian las claves de los datos existentes.

## Validación

- Prueba de guardado con WordPress: 13 comprobaciones, PASS; borrador eliminado.
- Prueba en Chrome de añadir, editar, eliminar, guardar desde otra sección,
  aprobación, vista móvil y alternativa sin JavaScript: PASS.
- Regresión del editor por secciones y verificación de sus campos guardados: PASS.
- Usuario, sesiones, tour y archivo de prueba temporales eliminados.

La publicación de la etiqueta `v0.6.2` genera `tourism-core.zip` mediante GitHub
Actions. La prueba posterior encontró un fallo de identificación en el
actualizador de 0.6.1 y 0.6.2: necesitan reemplazarse una vez por 0.6.3 o
posterior. Véase [la corrección](release-0.6.3.md) y [la actualización real
verificada](release-0.6.4.md). La consulta de versiones mantiene una caché
de hasta una hora.
