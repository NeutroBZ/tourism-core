# Tourism Core 0.6.4

Entrega de verificación del ciclo nativo de actualización desde 0.6.3.
Incluye el editor de videos y reseñas de 0.6.2 y la corrección del identificador
del plugin de 0.6.3; no añade otra funcionalidad.

La verificación usa una copia del ZIP publicado de 0.6.3 en una carpeta
temporal separada de los archivos del proyecto. Consulta la release pública
de GitHub mediante el actualizador instalado en esa copia y ejecuta
`Plugin_Upgrader` para pasar a 0.6.4. Los resultados se comprueban después
de la publicación; no se actualizan sitios de clientes.

Resultado del 22 de octubre de 2026: **PASS**. WordPress detectó 0.6.4 desde
el ZIP publicado sin modificar de 0.6.3, descargó el paquete y completó la
instalación con `Plugin_Upgrader`. Se verificaron la versión instalada,
la carpeta `tourism-core`, el nuevo archivo `assets/js/tour-extras.js` y
la sección de videos/reseñas. La lista de plugins activos y la configuración
de módulos del sitio real permanecieron iguales; se restauraron sus cachés.

Los sitios que siguen en 0.6.1 o 0.6.2 necesitan instalar el ZIP corregido
una vez. Desde 0.6.3 ya pueden recibir las siguientes versiones desde WordPress.
