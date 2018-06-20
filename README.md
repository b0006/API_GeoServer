# API_GeoServer
<h2>Реализацтя REST API Geoserver'a на PHP</h2>

<h3>Пример использования</h3>

```php
include "Geoserver.php";

$geoserver = new Geoserver("http://localhost:8080/geoserver/", "admin", "geoserver");
$res = $geoserver->listWorkspaces();

print_r($res);
```

<h3>Реализованы следующие функции:</h3>
<h4>Workspace</h4>
<ul>
  <li>получить список рабочих групп (workspaces)</li>
  <li>создать рабочую группу</li>
  <li>удалить рабочую группу</li>
</ul>
<h4>Datastore</h4>
<ul>
  <li>создать SHP хранилище</li>
  <li>удалить векторное хранилище</li>
</ul>
<h4>Coveragestore</h4>
<ul>
  <li>список растровых хранилищ</li>
  <li>получить определенное хранилище</li>
  <li>создание GeoTIFF хранилища посредством JSON</li>
  <li>создание GeoTIFF хранилища посредством XML</li>
  <li>обновление GeoTIFF хранилища посредством JSON</li>
  <li>удалить растровое хранилище</li>
</ul>
