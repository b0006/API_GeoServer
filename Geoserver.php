<?php
interface IWorkspace {
    public function listWorkspaces(); // список рабочих групп (workspaces)
    public function createWorkspace($workspaceName); // создать рабочую группу
    public function deleteWorkspace($workspaceName); // удалить рабочую группу
}

interface IDatastore
{
    public function createShpDirDataStore($datastoreName, $workspaceName, $location);       // создать SHP хранилище
    public function deleteDataStore($datastoreName, $workspaceName);                        // удалить векторное хранилище
}

interface ICoveragestore
{
    public function listCoveragestores($workspaceName);                                         // список растровых хранилищ
    public function getCoveragestore($workspaceName, $datastoreName);                           // получить определенное хранилище
    public function createGeoTiffCoverageStoreJson($datastoreName, $workspaceName, $location, $description = "");  // создание GeoTIFF хранилища посредством JSON
    public function createGeoTiffCoverageStoreXml($datastoreName, $workspaceName, $location, $description = "");   // создание GeoTIFF хранилища посредством XML
    public function updateGeoTiffDataStoreJson($datastoreName, $workspaceName, $location);      // обновление GeoTIFF хранилища посредством JSON
    public function deleteCoveragestores($datastoreName, $workspaceName);                       // удалить растровое хранилище
}

class Geoserver
{
    private $serverUrl = '';
    private $username = '';
    private $password = '';

    public function __construct($serverUrl, $username = '', $password = '') {
        if (substr($serverUrl, -1) !== '/') $serverUrl .= '/';
        $this->serverUrl = $serverUrl;
        $this->username = $username;
        $this->password = $password;
    }

    protected function runApi($apiPath, $method = 'GET', $data = '', $contentType = 'text/xml') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->serverUrl.'rest/'.$apiPath);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else if ($method == 'DELETE' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($data != '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                array(
                    "Content-Type: $contentType",
                    'Content-Length: '.strlen($data),
                )
            );
        }


        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rslt = curl_exec($ch);
        $info = curl_getinfo($ch);

        $result = array(
            "result_message" => $rslt,
            "url" => $info["url"],
            "http_code" => $info["http_code"],
        );

        if ($info['http_code'] == 401) {
            return 'Access denied';
        } else {
            return $result;
        }
    }
}

class Workspace extends Geoserver implements IWorkspace
{
    public function __construct($serverUrl, $username = '', $password = '') {
        parent::__construct($serverUrl, $username, $password);
    }

    public function listWorkspaces() {
        return $this->runApi('workspaces');
    }

    public function createWorkspace($workspaceName) {
        return $this->runApi('workspaces', 'POST', '<workspace><name>'.htmlentities($workspaceName, ENT_COMPAT).'</name></workspace>');
    }

    public function deleteWorkspace($workspaceName) {
        return $this->runApi('workspaces/'.urlencode($workspaceName), 'DELETE');
    }
}

class Datastore extends Geoserver implements IDatastore
{
    public function __construct($serverUrl, $username = '', $password = '') {
        parent::__construct($serverUrl, $username, $password);
    }

    public function createShpDirDataStore($datastoreName, $workspaceName, $location) {
        return $this->runApi('workspaces/'.urlencode($workspaceName).'/datastores', 'POST', '<dataStore>
			<name>'.htmlentities($datastoreName, ENT_COMPAT).'</name>
			<type>Directory of spatial files (shapefiles)</type>
			<enabled>true</enabled>
			<connectionParameters>
				<entry key="memory mapped buffer">false</entry>
				<entry key="timezone">America/Boise</entry>
				<entry key="create spatial index">true</entry>
				<entry key="charset">ISO-8859-1</entry>
				<entry key="filetype">shapefile</entry>
				<entry key="cache and reuse memory maps">true</entry>
				<entry key="url">file:'.htmlentities($location, ENT_COMPAT).'</entry>
				<entry key="namespace">'.htmlentities($workspaceName, ENT_COMPAT).'</entry>
			</connectionParameters>
			</dataStore>');
    }

    public function deleteDataStore($datastoreName, $workspaceName) {
        return $this->runApi('workspaces/'.urlencode($workspaceName).'/datastores/'.urlencode($datastoreName), 'DELETE');
    }
}

class Coveragestore extends Geoserver implements ICoveragestore
{
    public function __construct($serverUrl, $username = '', $password = '') {
        parent::__construct($serverUrl, $username, $password);
    }

    public function listCoveragestores($workspaceName) {
        return $this->runApi('workspaces/'.urlencode($workspaceName).'/coveragestores');
    }

    public function getCoveragestore($workspaceName, $datastoreName) {
        return $this->runApi('workspaces/'.urlencode($workspaceName).'/coveragestores/' . urlencode($datastoreName));
    }

    public function createGeoTiffCoverageStoreJson($datastoreName, $workspaceName, $location, $description = "") {

        $data = array(
            "coverageStore" => array(
                "type" => "GeoTIFF",
                "description" => htmlentities($description, ENT_COMPAT),
                "enabled" => true,
                "name" => htmlentities($datastoreName, ENT_COMPAT),
                "url" => 'file:' . htmlentities($location, ENT_COMPAT),
                "workspace" => array(
                    "name" => htmlentities($workspaceName, ENT_COMPAT)
                )
            )
        );

        $data_json = json_encode($data);

        return $this->runApi('workspaces/' . urlencode($workspaceName). '/coveragestores', 'POST', $data_json, $contentType = "application/json");
    }

    public function createGeoTiffCoverageStoreXml($datastoreName, $workspaceName, $location, $description = "") {

        $data = '<coverageStore>
			<name>'.htmlentities($datastoreName, ENT_COMPAT).'</name>
			<description>'.htmlentities($description, ENT_COMPAT).'</description>
			<type>GeoTIFF</type>
			<enabled>true</enabled>
			<workspace>'.htmlentities($workspaceName, ENT_COMPAT).'</workspace>
	  		<url>file:'.htmlentities($location, ENT_COMPAT).'</url>
		</coverageStore>;';


        return $this->runApi('workspaces/' . urlencode($workspaceName). '/coveragestores', 'POST', $data);
    }


    public function updateGeoTiffDataStoreJson($datastoreName, $workspaceName, $location) {

        $data = array(
            "coverageStore" => array(
                "name" => htmlentities($datastoreName, ENT_COMPAT),
                "description" => "Sams11x1e22 ASCII GRID coverage of Global rainfall.",
                "type" => "GeoTIFF",
                "enabled" => true,
                "url" => "file:" . htmlentities($location, ENT_COMPAT),
                "workspace" => array(
                    "name" => htmlentities($workspaceName, ENT_COMPAT),
                ),
                "_default" => false,
            )
        );

        $data_json = json_encode($data);

        return $this->runApi('workspaces/' . urlencode($workspaceName). '/coveragestores/' . htmlentities($datastoreName, ENT_COMPAT) . ".json", 'PUT', $data_json, "application/json");
    }

    public function deleteCoveragestores($datastoreName, $workspaceName) {
        return $this->runApi('workspaces/'.urlencode($workspaceName).'/coveragestores/'.urlencode($datastoreName), 'DELETE');
    }
}