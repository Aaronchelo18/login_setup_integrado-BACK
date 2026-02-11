## Proyecto codigo5 - 

### Pasos para poder desplegar el proyecto en docker en el equipo de cóimputo del TI desarrollador

#### 1. Descargar el proyecto del repositorio git.upeu.edu.pe haciendo uso del comando, considerando la rama en desarrollo
 
 ```git clone URL_PROYECTO -b BRANCH```

#### 2. Para poder construir y levantar el proyecto en docker ejecute el siguiente comando desde la raíz del proyecto: 

##### 2.1 Windows

```.\runapp.bat```
##### 2.1 Linux

```chmod +x runapp.sh```

```./runapp.sh```

#### 3. Para poder acceder al contenedor por bash ejecute (el nombre del servicio lo pueden visualizar en el archivo docker-compose.yml): 

```docker exec -it cd5-setup-back bash```

#### 4. Una vez dentro del contendor, instalar composer y darle permisos a la aplicación

```composer install```

```chmod -R 777 storage```

``` php artisan cache:clear```
```php artisan key:generate ```

#### 5. Verificar que la aplicación este inicializada **en el puerto que esta definido en su docker-compose.yml.

<http://localhost:5017>

#### 6. Una vez verificado ya puede programar localmente y versionar sus cambios en su brach.


