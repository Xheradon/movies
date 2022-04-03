## Uso del proyecto

1. Clonar el proyecto en local
2. Ejecutar `docker-compose -f docker-compose.yml up -d`
3. Esperar a la inicialización de la base de datos y comprobar que el proyecto es visible en 
[localhost/admin](http://localhost/admin)
4. Conectarse al contenedor de php con `docker exec -it <nombre contenedor php> sh`
5. Ejecutar el comando de importación de datos con `bin/console app:import -vv`

## Consideraciones
1. Se toma el nombre completo de actores y directores como único ya que de lo contrario cada entrada del CSV sería un
registro nuevo en la base de datos.
2. Las claves primarias usadas son Uuids v4. Los ids que devuelve el CSV se han registrado como `import_id` e 
`import_source` en la base de datos para diferenciar las creadas manualmente de las importadas o permitir la importación
de diferentes fuentes (aunque en este caso seguramente habría que modificar la estructura para que una película tenga 
diferentes import ids).
3. La importación de CSV no modifica registros ya insertados, por un lado por razones de performance y por otro lado
por considerar que una modificación manual debería mantenerse.
4. No se ha usado el ORM para la importación del CSV por no ser óptimo y tardar mucho.
5. Se ha sacrificado uso de RAM en la importación del CSV para agilizar el proceso. Para un uso real y según necesidades
se podría optimizarlo para no consumir tanta.
