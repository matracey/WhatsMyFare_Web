<?php
require_once 'Rest.inc.php';
require_once 'http_codes.php';

function uriParser()
{
	//not really tested, treat as pseudocode
	//doesn't remove the base url
	$params = array();
	$domain = $_SERVER['HTTP_HOST'];
	$parts = explode('/', $_SERVER['REQUEST_URI']);

	// Find the /api/ segment of our URI
	if($domain == "api.whatsmyfare.ie") $startIndex = 0;
	else $startIndex = array_search("api", $parts); // Index of the query start

	$i=$startIndex+1;
	if (isset($parts[$i]))
	{
		$key = "api_key";
		if( isset($parts[$i]) ) $val = $parts[$i];
		$params[$key] = $val;
		$i++;

		$key = "rquest";
		if( isset($parts[$i]) ) $val = $parts[$i];
		$params[$key] = $val;
		$i++;

		while ($i < count($parts))
		{
			$key = $parts[$i];
			if (isset($parts[$i+1])) $val = $parts[$i+1];
			// echo $key . ': ' . addslashes($val) . "\r\n";
			// exit();
			$i=$i+2;
			$params[$key] = addslashes($val);
		}
	}

	//and make it work with your exsisting code
	$_GET = array_merge($_GET, $params);
	// echo "<pre>";
	// var_dump(rawurldecode($_GET['name']));
	// echo "</pre>";
	// exit();
}

class API extends REST
{
	public $data = "";
	const DB_SERVER = ''; // Server const
	const DB_USERNM = 'root'; // Database Username const
	const DB_PASSWD = ''; // Database Password const
	const DB_SCNAME = 'whats_my_fare_db'; //const DB_SCNAME = 'whats_my_fare_db'; // Database Schema Name const

	private $db = NULL;

	public function __construct()
	{
		parent::__construct(); // INIT PARENT
		$this->dbConnect();
	}

	private function dbConnect()
	{
		$this->db = mysql_connect(self::DB_SERVER, self::DB_USERNM, self::DB_PASSWD); // creating the database connection
		if ($this->db)
		{
			mysql_set_charset("utf8");
			mysql_select_db(self::DB_SCNAME, $this->db); // selecting the DB to use with the current DB connection.
		}
	}

	public function processCall()
	{
		$functionCalled = strtolower( trim( str_replace("/", "", $_GET['rquest']) ) ); // Pre-process the request string.

		// echo $functionCalled;
		$validKeys = array();
		if ($resultSet = mysql_query("SELECT `key`, `permission_id` FROM `api_keys`") and mysql_num_rows($resultSet)) while ($row = mysql_fetch_array($resultSet)) $validKeys[] = $row;

		$allowed = false;
		$permission = false;

		foreach ($validKeys as $value) if ($value['key'] === $_GET['api_key'])
		{
			$allowed = true;
			$permission = (int)$value['permission_id']; // 1 is read-only, 2 is readwrite
		}

		// var_dump($validKeys, $permission, strstr($functionCalled, "get"));
		// exit();

		if($permission === 1 and !strstr($functionCalled, "get"))
		{
			/*
			 * If they have READ-ONLY access and try to call a method
			 * other than a get method, then they're not allowed.
			 */

			$allowed = false;
		}

		if ($allowed)
		{
			if ((int)method_exists($this, $functionCalled)) 
			{
				$this->$functionCalled(); // If there is a method matching the request, call it!
			}
			$this->response('', NOT_FOUND); // If there's no method matching the request, return a 404 error.
		}
		$this->response('', FORBIDDEN);
	}

	private function checkMethod($method)
	{
		if ($this->get_request_method() != $method)
		{
			$this->response('', NOT_ACCEPTABLE); // NOT ACCEPTABLE: You didn't come through the right door. Fuck off.
		}
	}

	private function siteStatus()
	{
		$this->getSettingValue('SiteActive');
	}

	private function appStatus()
	{
		$this->getSettingValue('AppActive');
	}

	private function getSettingValue($key)
	{
		$this->checkMethod('GET');

		$query = "SELECT `value` FROM `site_settings` WHERE `key` = '$key' LIMIT 1;";
		$sql = mysql_query($query, $this->db);
		$error = mysql_error();

		if(empty($error))
		{
			// Return Result
			if(mysql_num_rows($sql) > 0)
			{
				$result = mysql_fetch_array($sql, MYSQL_ASSOC);
				$this->response($this->encode_data($result), OK);
			}
			$this->response ('', NO_CONTENT);
		}
		$this->response(encode_data($error), BAD_REQUEST);
	}

	private function addAPIKey()
	{
		$this->checkMethod("POST");

		if ( (isset($this->_request['owner']) and !empty($this->_request['owner'])) and isset($this->_request['permission_id']) and ($this->_request !== "" or $this->_request !== " ") )
		{
			$apiKey = base64_encode( rand() );
			$owner = $this->_request['owner'];
			$p_id = $this->_request['permission_id'];
			$sql = "INSERT INTO `api_keys` (`key`, `owner`, `permission_id`) VALUES ('$apiKey', '$owner', '$p_id')";

			if (mysql_query($sql, $this->db))
			{
				$result = array('status' => "Success", 'msg' => "Successfully added API key.");
				$this->response($this->encode_data( $result ), OK);
			}
		}
		$this->response('', BAD_REQUEST);

	}

	// Service Methods

	// Get All Services
	// Get All Active Services
	// Get Service for Stop?

	private function getAllServices($activeOnly = false)
	{
		$this->checkMethod("GET");

		$query = "SELECT ";
		if ( !isset($this->_request['id']) ) $query .= "`id`, ";
		if ( !isset($this->_request['name']) ) $query .= "`name`";
		if ($activeOnly === false) $query .= ", `is_active`";
		$query .= " FROM `services` WHERE `deleted` = 0";
		if ($activeOnly) $query .= " AND `is_active` = 1";
		if (isset($this->_request['id'])) $query .= " AND `id` = " . (int)$this->_request['id'];
		if (isset($this->_request['name'])) $query .= " AND `name` = " . $this->request['name'];
		if (isset($this->_request['id']) or isset($this->_request['name']))$query .= " LIMIT 1";
		$query .= ";";

		$sql = mysql_query($query, $this->db);
		$error = mysql_error();

		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0)
		{
			$result = array();
			while ($item = mysql_fetch_array($sql, MYSQL_ASSOC)) $result[] = $item;

			$this->response( $this->encode_data( $result ), OK );
		}
		$this->response('', NO_CONTENT);
	}

	private function getAllActiveServices()
	{
		$this->getAllServices(true);
	}

	private function getServiceIDByName()
	{
		if (isset($_request['name'])) $this->getAllServices();
		$this->response('', BAD_REQUEST);
	}

	// Price Methods

	// Get All Prices
	// Get All Active Prices
	// Get Price For Price Code
	private function getAllAdultPrices()
	{
		$this->getAllPrices(false, "adult");
	}
	private function getAllChildPrices()
	{
		$this->getAllPrices(false, "child");
	}
	private function getAllStudentPrices()
	{
		$this->getAllPrices(false, "student");
	}

	private function getAllActiveAdultPrices()
	{
		$this->getAllPrices(true, "adult");
	}
	private function getAllActiveChildPrices()
	{
		$this->getAllPrices(true, "child");
	}
	private function getAllActiveStudentPrices()
	{
		$this->getAllPrices(true, "student");
	}

	private function getAllActivePrices()
	{
		$this->getAllPrices(true);
	}
	private function getActivePriceForCode()
	{
		if( isset($this->_request['code']) ) $this->getAllPrices(true);
		$this->response('', BAD_REQUEST);
	}

	private function getAllPrices($activeOnly = false, $bracket = NULL)
	{
		$this->checkMethod("GET");

		$query = "SELECT `id`, `name`, ";
		if ( !isset($this->_request['code']) ) $query .= "`price_code`, ";
		if ($bracket === NULL or $bracket === "adult") $query .= "`adult_single_cash`, `adult_single_offpeak_cash`, `adult_return_cash`, `adult_single_leap`, `adult_single_offpeak_leap`, `adult_return_leap`, `adult_return_offpeak_leap`, ";
		if ($bracket === NULL or $bracket === "child") $query .= "`child_single_cash`, `child_single_offpeak_cash`, `child_return_cash`, `child_single_leap`, `child_single_offpeak_leap`, `child_return_leap`, `child_return_offpeak_leap`, ";
		if ($bracket === NULL or $bracket === "student") $query .= "`student_single_cash`, `student_single_offpeak_cash`, `student_return_cash`, `student_single_leap`, `student_single_offpeak_leap`, `student_return_leap`, `student_return_offpeak_leap`, ";
		$query .= "`is_active`, `version`";
		if ($activeOnly === false) $query .= ", `is_active`";
		$query .= " FROM `prices` WHERE `deleted` = 0";
		if ($activeOnly) $query .= " AND `is_active` = 1";
		if (isset($this->_request['id'])) $query .= " AND `id` = " . (int)$this->_request['id'];
		if (isset($this->_request['code'])) $query .= " AND `price_code` = '" . $this->_request['code'] . "'";
		if (isset($this->_request['id']) or isset($this->_request['code'])) $query .=  " LIMIT 1";
		$query .= ";";
		$sql = mysql_query($query, $this->db);
		$error = mysql_error();

		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0)
		{
			$result = array();
			while ($item = mysql_fetch_array($sql, MYSQL_ASSOC)) $result[] = $item;

			$this->response( $this->encode_data( $result ), OK );
		}
		$this->response('', NO_CONTENT);
	}

	// Fare Methods

	// Get All Fares for Stop ID
	// Get All Fares
	private function getAllActiveFares()
	{
		$this->getAllFares(true);
	}

	private function getAllFaresForStop()
	{
		if (isset($this->_request['stop'])) $this->getAllFares(true);
		$this->response('', BAD_REQUEST);
	}

	private function getFareForStops()
	{
		if (isset($this->_request['from']) and isset($this->_request['to'])) $this->getAllFares();
		$this->response('', BAD_REQUEST);
	}

	private function getActiveFareForStops()
	{
		if (isset($this->_request['from']) and isset($this->_request['to'])) $this->getAllFares(true);
		$this->response('', BAD_REQUEST);
	}

	private function getAllActiveFaresForStop()
	{
		if (isset($this->_request['stop'])) $this->getAllFares(true);
		$this->response('', BAD_REQUEST);
	}

	// private function getAllActiveFaresForStops()
	// {
	// 	if (isset($this->_request['from']) and isset($this->_request['to'])) $this->getAllFares(true);
	// 	$this->response('', BAD_REQUEST);
	// }

	private function getAllFares($activeOnly = false)
	{
		$this->checkMethod("GET");

		$query = "SELECT `id`, `stop_from`, `stop_to`, `fare_price_code`";
		if ($activeOnly === false) $query .= ", `is_active`";
		$query .= " FROM `fares` WHERE `deleted` = 0";
		if ($activeOnly) $query .= " AND `is_active` = 1";
		if (isset($this->_request['id'])) $query .= " AND `id` = " . (int)$this->_request['id'];
		if (isset($this->_request['stop'])) $query .= " AND `stop_from` = " . (int)$this->_request['stop'];
		if (isset($this->_request['from'])) $query .= " AND `stop_from` = " . (int)$this->_request['from'];
		if (isset($this->_request['to'])) $query .= " AND `stop_to` = " . (int)$this->_request['to'];
		if (isset($this->_request['id']) or (isset($this->_request['from']) and isset($this->_request['to']))) $query .=  " LIMIT 1";
		$query .= ";";

		$sql = mysql_query($query, $this->db);

		$error = mysql_error();
		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0)
		{
			$result = array();
			while ($item = mysql_fetch_array($sql, MYSQL_ASSOC)) $result[] = $item;
			$this->response( $this->encode_data( $result ), OK );
		}
		$this->response('', NO_CONTENT);
	}

	// Stop Methods

	// Get All Stop Names + IDs //
	// Get All Active Stops for Service //
	// Create New Stop
	// Edit Stop //
	// Soft Delete / Hard Delete Stop //
	// Activate / De-Activate Stop //

	private function getStop()
	{
		// By ID or By Name
		if ( isset($this->_request['id']) or isset($this->_request['name']) ) $this->getAllStops();
		else $this->response('', BAD_REQUEST);
	}

	private function getActiveStop()
	{
		// By ID or By Name
		if ( isset($this->_request['id']) or isset($this->_request['name']) ) $this->getAllStops(true);
		else $this->response('', BAD_REQUEST);
	}


	private function getStopForService()
	{
		if ( ( isset($this->_request['id']) or isset($this->_request['name']) ) and isset($this->_request['type_id']) )
		{
			$this->getAllStops();
		}else $this->response('', BAD_REQUEST);
	}

	private function getActiveStopForService()
	{
		if ( ( isset($this->_request['id']) or isset($this->_request['name']) ) and isset($this->_request['type_id']) )
		{
			$this->getAllStops(true);
		}else $this->response('', BAD_REQUEST);
	}

	private function getAllActiveStops()
	{
		$this->getAllStops(true);
	}

	private function getAllActiveStopsForService()
	{
		$this->getAllStopsForService(true);
	}

	private function getAllStopsForService($activeOnly = false)
	{
		$this->checkMethod("GET");

		if ( !isset($this->_request['id']) ) $this->response('', BAD_REQUEST);
		$id = $this->_request['id'];
		$query = "SELECT `stop_id` FROM `stop_services` WHERE `service_id` = $id";

		$sql = mysql_query($query, $this->db);

		$error = mysql_error();
		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0)
		{
			$result = array();
			$stopIDs = array();
			while ($item = mysql_fetch_array($sql, MYSQL_ASSOC))
			{
				$stopIDs[] = $item['stop_id'];
			}

			foreach ($stopIDs as $id)
			{
				$query = "SELECT `id`, `name`, `ainm`, `search_meta_tags`, `stop_type_id`";
				if ($activeOnly === false) $query .= ", `is_active`";
				$query .= " FROM `stops` WHERE `deleted` = 0 AND `id` = " . $id;
				if ($activeOnly) $query .= " AND `is_active` = 1";
				if (isset($this->_request['type_id'])) $query .= " AND `stop_type_id` = " . (int)$this->_request['type_id'];
				$query .=  " LIMIT 1;";

				$sql = mysql_query($query, $this->db);
				$error = mysql_error();
				if (!empty($error)) break; // AND LOG ERROR
				if (mysql_num_rows($sql) > 0) $result[] = mysql_fetch_array($sql, MYSQL_ASSOC);
				}
			$this->response( $this->encode_data( $result ), OK );
		}
		$this->response('', NO_CONTENT);
	}

	private function getActivePermissableStopsForStop()
	{
		$this->getPermissableStopsForStop(true);
	}

	private function getPermissableStopsForStop($activeOnly = false)
	{
		if(isset($this->_request['id']))
			{
				$id = $this->_request['id'];
				unset($this->_request['id']);
				$this->_request['to'] = $id;
				$this->getAllStops($activeOnly, true);
			}
		else $this->response('', BAD_REQUEST);

	}

	private function getAllStops($activeOnly = false, $permissableOnly = false)
	{
		$this->checkMethod("GET");

		if ($permissableOnly) {
			// Generate the query for PERMISSABLE STOPS.
			$query = "SELECT `origin_id` AS `id`, `origin_name`  AS `name`, `origin_ainm` AS `ainm`, `origin_meta_tags` AS `search_meta_tags`, `origin_type_id` AS `stop_type_id`";

			// If we're only looking for ACTIVE stops, then we already know what the value of is_active is going to be, so we only need it if activeOnly is false.
			if(!$activeOnly) $query .= ", `fare_is_active` AS `is_active`";

			$query .= "FROM `vw_permissable_stops` WHERE `deleted` = 0 AND `destin_id` = ".$this->_request['to'];
			if ($activeOnly) $query .= " AND `fare_is_active` = 1";
			$query .= ";";
		}else{
			// Otherwise, generate the standard STOPS query.
			$query = "SELECT `id`, `name`, `ainm`, `search_meta_tags`, `stop_type_id`";
			if ($activeOnly === false) $query .= ", `is_active`";
			$query .= " FROM `stops` WHERE `deleted` = 0";
			if ($activeOnly) $query .= " AND `is_active` = 1";
			if (isset($this->_request['id'])) $query .= " AND `id` = " . (int)$this->_request['id'];
			if (isset($this->_request['name'])) $query .= " AND `name` LIKE '%" . addslashes(rawurldecode($this->_request['name']))."%'"; 
			if (isset($this->_request['type_id'])) $query .= " AND `stop_type_id` = " . (int)$this->_request['type_id'];
			if (isset($this->_request['id'])) $query .=  " LIMIT 1";
			$query .= ";";
		}

		$sql = mysql_query($query, $this->db);

		$error = mysql_error();
		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0)
		{
			$result = array();
			while ($item = mysql_fetch_array($sql, MYSQL_ASSOC))
			{
				$result[] = $item;
			}
			
			foreach ($result as $key => $item)
			{
				$query = "SELECT `service_id` FROM `stop_services` WHERE `stop_id` = ".$item['id']." LIMIT 1;";
				$sql = mysql_query($query, $this->db);
				$error = mysql_error();
				$res = mysql_fetch_array($sql);
				if(empty($error)) $result[$key]['service_id'] = $res['service_id'];

				$query = "SELECT `route_id` FROM `stop_routes` WHERE `stop_id` = ".$item['id']." LIMIT 1;";
				$sql = mysql_query($query, $this->db);
				$error = mysql_error();
				$res = mysql_fetch_array($sql);
				if(empty($error)) $result[$key]['route_id'] = $res['route_id'];
			}

			$this->response( $this->encode_data( $result ), OK );
		}
		$this->response('', NO_CONTENT);
	}

	private function deleteStop()
	{
		$this->checkMethod("DELETE");

		if(!isset($this->_request['id'])) $this->_request['id'] = '0';
		$id = (int)$this->_request['id'];
		if ($id > 0)
		{
			if (mysql_query("UPDATE `stops` SET `deleted` = 1 WHERE `id` = $id LIMIT 1") and mysql_affected_rows() > 0)
			{
				$result = array('status' => "Success", 'msg' => "Successfully deleted stop record.");
				$this->response($this->encode_data( $result ), OK);
			}
			$this->response('', NO_CONTENT);
		}
		$this->response('', BAD_REQUEST);
	}

	private function hardDeleteStop()
	{
		$this->checkMethod("DELETE");

		if(!isset($this->_request['id'])) $this->_request['id'] = '0';
		$id = (int)$this->_request['id'];
		if ($id > 0)
		{
			if (mysql_query("DELETE FROM `stops` WHERE `id` = $id LIMIT 1") and mysql_affected_rows() > 0)
			{
				$result = array('status' => "Success", 'msg' => "Successfully deleted stop record.");
				$this->response($this->encode_data( $result ),OK);
			}
			$this->response('', NO_CONTENT); // NO CONTENT
		}
		$this->response('', BAD_REQUEST);
	}

	private function deactivateStop()
	{
		$this->checkMethod("POST");
		$this->editStop(false); // FALSE for DE-ACTIVATE
	}

	private function activateStop()
	{
		$this->checkMethod("POST");
		$this->editStop(true); // TRUE for ACTIVATE
	}

	private function editStop($activate = NULL)
	{
		if (!isset($activate)) $this->checkMethod("POST");
		if(!isset($this->_request['id'])) $this->_request['id'] = '0';
		$id = (int)$this->_request['id'];

		// TO-DO: Verify the timestamp business
		if ($activate === false or $activate === true)
		{
			foreach ($this->_request as $key => $value) unset($this->_request[$key]);
			if ($activate === false) $this->_request['active'] = "0";
			else $this->_request['active'] = "1";
		}

		$result_set = mysql_query("SELECT id FROM `stop_types`");
		$stop_types = array();
		while ($stop_type = mysql_fetch_array($result_set, MYSQL_ASSOC)) $stop_types[] = $stop_type['id'];

		// Generate SQL Statement
		$sql = "UPDATE `stops` SET";

		// Set up the variables that should be updated.
		$fields = "";
		if ( isset($this->_request['name']) ) $name = $this->_request['name'];
		if ( isset($this->_request['ainm']) ) $ainm = $this->_request['ainm'];
		if ( isset($this->_request['meta_tags']) ) $meta_tags = $this->_request['meta_tags'];
		if ( isset($this->_request['type_id']) ) $type_id = $this->_request['type_id'];
		if ( isset($this->_request['active']) )$active = $this->_request['active'];

		// If there's something valid specified for each field, then we'll add the SET statement for that field to the SQL statement.
		if ( isset($name) and !empty($name) ) $fields .= " `name` = '" . $name . "', ";
		if ( isset($ainm) and !empty($ainm) ) $fields .= " `ainm` = '" . $ainm . "', ";
		if ( isset($meta_tags) and !empty($meta_tags) ) $fields .= " `search_meta_tags` = '" . $meta_tags . "', ";
		if ( isset($type_id) and (array_search($type_id, $stop_types)) ) $fields .= " `user_type_id` = '" . $type_id . "', ";
		if ( isset($active) and ($active === "0" or $active === "1")  ) $fields .= " `is_active` = " . $active . ", ";

		// Now let's trim off any extraneous , from the statement
		$fields = rtrim($fields, ", ");
		// if there is, then we'll finish up the statement
		$sql .= $fields . " WHERE `id` = $id LIMIT 1;";

		if ($id > 0 and !empty($fields))
		{
			if (mysql_query($sql, $this->db) and mysql_affected_rows() > 0)
			{
				$result = array('status' => "Success", 'msg' => "Successfully updated stop record.");
				$this->response($this->encode_data( $result ), OK);
			}
			$this->response('', NO_CONTENT);
		}
		$this->response('', BAD_REQUEST);
	}

	private function addStop()
	{
		// Make sure that the request got here via POST.
		$this->checkMethod("POST");

		if ( isset($this->_request['name']) ) $name = $this->_request['name'];
		if ( isset($this->_request['ainm']) ) $ainm = $this->_request['ainm'];
		if ( isset($this->_request['meta_tags']) ) $meta_tags = $this->_request['meta_tags'];
		if ( isset($this->_request['type_id']) ) $type_id = $this->_request['type_id'];

		// Validate Input
		if ( !empty($name) and !empty($meta_tags) and isset($type_id) )
		{
			$query = "INSERT INTO `stops` (`name`, ";
			if (isset($ainm) and !empty($ainm)) $query .= "`ainm`, ";
			$query .= "`search_meta_tags`, `stop_type_id`) VALUES ('". $name . "', '";
			if (isset($ainm) and !empty($ainm)) $query .= $ainm . "', '";
			$query .= $meta_tags . "', '" . $type_id . "')";

			if( mysql_query($query, $this->db) )
			{
				$result = array('status' => "Success", 'msg' => "Successfully added stop record.");
				$this->response($this->encode_data( $result ), OK);
			}else 
			{
				$result = array('status' => "Failed", 'msg' => "Could not add stop record.", 'error' => mysql_error());
				$this->response($this->encode_data( $result ), BAD_REQUEST);
			}
		}
		// The last kind of response we'll give will be a bad request.
		// We'll give this response only if the caller doesn't call our method correctly ( i.e there's no email or password, or validation failed. )
		$error = array('status' => "Failed", 'message' => "Invalid Email Address, Name or Password");
		$this->response($this->encode_data($error), BAD_REQUEST); // BAD REQUEST: This is what you get if you don't format your API call correctly!!!
	}

	// Record IP Address
	private function addConnectionLog()
        {
                // Make sure that the request got here via POST.
                $this->checkMethod("POST");

                if ( isset($this->_request['ip']) ) $ip = $this->_request['ip'];

                // Validate Input
                if ( !empty($ip) )
                {
                        $query = "INSERT INTO `connections_log` (`ip_address`) VALUES ('". $ip . "');";

                        if( mysql_query($query, $this->db) )
                        {
                                $result = array('status' => "Success", 'msg' => "Successfully added connection log record.");
                                $this->response($this->encode_data( $result ), OK);
                        }else
                        {
                                $result = array('status' => "Failed", 'msg' => "Could not add connection log record.", 'error' => mysql_error());
                                $this->response($this->encode_data( $result ), BAD_REQUEST);
                        }
                }
                // The last kind of response we'll give will be a bad request.
                // We'll give this response only if the caller doesn't call our method correctly ( i.e there's no email or password, or validation failed. )
                $error = array('status' => "Failed", 'message' => "Invalid Email Address, Name or Password");
                $this->response($this->encode_data($error), BAD_REQUEST); // BAD REQUEST: This is what you get if you don't format your API call correctly!!!
        }

	// Stop Type Methods

	private function getAllStopTypes($activeOnly = false)
	{
		$this->checkMethod("GET");

		$query = "SELECT `id`, `name`";
		if ($activeOnly === false) $query .= ", `is_active`";
		$query .= " FROM `stop_types` WHERE `deleted` = 0";
		if ($activeOnly) $query .= " AND `is_active` = 1";
		if (isset($this->_request['id'])) $query .= " AND `id` = " . (int)$this->_request['id'];
		if (isset($this->_request['id'])) $query .=  " LIMIT 1";
		$query .= ";";

		$sql = mysql_query($query, $this->db);

		$error = mysql_error();
		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0)
		{
			$result = array();
			while ($item = mysql_fetch_array($sql, MYSQL_ASSOC))
			{
				$result[] = $item;
			}
			$this->response( $this->encode_data( $result ), OK );
		}
		$this->response('', NO_CONTENT);
	}

	// User Methods

	// getAllUsers / getAllActiveUsers / Get All Active Users - GET request (NO PARAMETERS) && Get All Users - GET request: Back Office use (NO PARAMETERS) //
	// getUser / Get User Account - GET request - User ID and/or User Type ID: returns user record //
	// getAllUserTypes / Get All User Types - GET request //
	// login / Login - POST request - email address and password: returns user record. (Active user accounts only) //
	// addUser / Create New User / Register - POST request - email address, full name and password: returns success message. //
	// editUser / Edit User Account - POST request - User ID, email address, full name and password: all fields optional except User ID: returns success message. //
	// activateUser / deactivateUser / deleteUser / hardDeleteUser / Delete User Account - DELETE request - User ID, marks user is_active as 0. //

		private function login()
	{
		// Make sure that the request got here via POST.
		$this->checkMethod("POST");

		$email = $this->_request['email'];
		$password = $this->_request['pwd'];

		// Validate Input
		if ( !empty($email) and !empty($password) )
		{
			if ( filter_var($email, FILTER_VALIDATE_EMAIL) )
			{
				// Query that finds the ONE user record that matches the email address and (HASHED using MD5) password that was received in POST request.
				$query = "
					SELECT `id`, `email_address`, `user_type_id` 
					FROM `users` 
					WHERE `email_address` = '$email' AND `password` = '" . md5($password) . "' AND is_active = 1 AND deleted = 0 LIMIT 1;"; //"' AND is_active = 1 LIMIT 1;";
				$sql = mysql_query($query, $this->db);
				// If there's more than 0 rows returned, then let's get the result!
				if(mysql_num_rows($sql) > 0)
				{
					$result = mysql_fetch_array($sql, MYSQL_ASSOC);
					// And let's let the application calling us know that that everything's all good.
					$this->response($this->encode_data($result), OK); // OK: Here's the user info you asked for.
				}
				$this->response('', NO_CONTENT); // NO CONTENT: This is the response if there's no user found. :( NOTE:: This must have a blank body.
			}
		}
		// The last kind of response we'll give will be a bad request.
		// We'll give this response only if the callee doesn't call our method correctly ( i.e there's no email or password, or validation failed. )
		$error = array('status' => "Failed", 'message' => "Invalid Email Address or Password");
		$this->response($this->encode_data($error), BAD_REQUEST); // BAD REQUEST: This is what you get if you don't format your API call correctly!!!
	}

	private function getAllUserTypes()
	{
		$this->checkMethod("GET");

		$sql = mysql_query("SELECT * FROM `user_types` LIMIT 2");
		$error = mysql_error();
		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0)
		{
			$result = array();
			while ($user_type = mysql_fetch_array($sql, MYSQL_ASSOC)) $result[] = $user_type;
			$this->response( $this->encode_data( $result ), OK );
		}
		$this->response('', NO_CONTENT);
	}

	private function getUser()
	{
		if ( isset($this->_request['id']) or isset($this->_request['type_id']) ) $this->getAllUsers();
		else $this->response('', BAD_REQUEST);
	}

	private function getAllActiveUsers()
	{
		$this->getAllUsers(true);
	}

	private function getAllUsers($activeOnly = false)
	{
		// Make sure that the request got here via GET
		$this->checkMethod("GET");

		$query = "SELECT `id`, `email_address`, `user_type_id`";
		if ($activeOnly === false) $query .= ", `is_active`";
		$query .= " FROM `users` WHERE `deleted` = 0";
		if ($activeOnly) $query .= " AND `is_active` = 1";
		if (isset($this->_request['id'])) $query .= " AND `id` = " . (int)$this->_request['id'];
		if (isset($this->_request['type_id'])) $query .= " AND `user_type_id` = " . (int)$this->_request['type_id'];
		if (isset($this->_request['id'])) $query .=  " LIMIT 1";
		$query .= ";";

		$sql = mysql_query($query, $this->db); // Get all of the currently ACTIVE users.

		$error = mysql_error();
		if (!empty($error)) $this->response($error."\n".$query, INTERNAL_SERVER_ERROR);
		if (mysql_num_rows($sql) > 0) // If there's more than 0 rows returned, then let's get the result!
		{
			$result = array(); // Delcare the result array.
			while ($user = mysql_fetch_array($sql, MYSQL_ASSOC)) // While there's more users to fetch...
			{
				$result[] = $user; // ... add the user to the result array.
			}
			// Now that we have all of the users in our result array, lets send it back to the caller.
			$this->response( $this->encode_data( $result ), OK ); //OK: Here's the user info you asked for.
		}
		$this->response('', NO_CONTENT); // NO CONTENT
	}

	private function deleteUser()
	{
		$this->checkMethod("DELETE");

		if(!isset($this->_request['id'])) $this->_request['id'] = '0';
		$id = (int)$this->_request['id'];
		if ($id > 0)
		{
			if (mysql_query("UPDATE `users` SET `deleted` = 1 WHERE `id` = $id LIMIT 1") and mysql_affected_rows() > 0)
			{
				$result = array('status' => "Success", 'msg' => "Successfully deleted user record.");
				$this->response($this->encode_data( $result ), OK);
			}
			$this->response('', NO_CONTENT); // NO CONTENT
		}
		$this->response('', BAD_REQUEST);
	}

	private function hardDeleteUser()
	{
		$this->checkMethod("DELETE");

		if(!isset($this->_request['id'])) $this->_request['id'] = '0';
		$id = (int)$this->_request['id'];
		if ($id > 0)
		{
			if (mysql_query("DELETE FROM `users` WHERE `id` = $id LIMIT 1") and mysql_affected_rows() > 0)
			{
				$result = array('status' => "Success", 'msg' => "Successfully deleted user record.");
				$this->response($this->encode_data( $result ),OK);
			}
			$this->response('', NO_CONTENT); // NO CONTENT
		}
		$this->response('', BAD_REQUEST);
	}

	private function deactivateUser()
	{
		$this->checkMethod("POST");
		$this->editUser(false); // FALSE for DE-ACTIVATE
	}

	private function activateUser()
	{
		$this->checkMethod("POST");
		$this->editUser(true); // TRUE for ACTIVATE
	}

	private function editUser($activate = NULL) // true for activate, false for de-activate, NULL for no change
	{
		if (!isset($activate)) $this->checkMethod("POST"); // if it has been set, then we've already been through this!
		$id = (int)$this->_request['id'];

		// TO-DO: Verify the timestamp business
		if ($activate === false or $activate === true) // true for activate, false for de-activate, NULL for no change
		{
			foreach ($this->_request as $key => $value) unset($this->_request[$key]);
			if ($activate === false) $this->_request['active'] = "0";
			else $this->_request['active'] = "1";
		}

		// Generate SQL Statement
		$sql = "UPDATE `users` SET";

		// Set up the variables that should be updated.
		$fields = "";
		if ( isset($this->_request['email'])) $email = $this->_request['email'];
		if ( isset($this->_request['pwd'])) $password = $this->_request['pwd'];
		if ( isset($this->_request['type_id'])) $type_id = (int) $this->_request['type_id'];
		if ( isset($this->_request['active'])) $active = $this->_request['active'];

		// If there's something valid specified for each field, then we'll add the SET statement for that field to the SQL statement.
		if ( isset($email) and !empty($email) ) $fields .= " `email_address` = '" . $email . "', ";
		if ( isset($password)  and !empty($password) ) $fields .= " `password` = '" . md5($password) . "', ";
		if ( isset($type_id) and ($type_id === 1 or $type_id === 2) ) $fields .= " `user_type_id` = '" . $type_id . "', ";
		if ( isset($active) and ($active === "0" or $active === "1")  ) $fields .= " `is_active` = " . $active . ", ";

		// Now let's trim off any extraneous , from the statement
		$fields = rtrim($fields, ", ");
		// if there is, then we'll finish up the statement
		$sql .= $fields . " WHERE `id` = $id LIMIT 1;";

		if ($id > 0 and !empty($fields))
		{
			if (mysql_query($sql, $this->db) and mysql_affected_rows() > 0)
			{
				$result = array('status' => "Success", 'msg' => "Successfully updated user record.");
				$this->response($this->encode_data( $result ), OK);
			}
			$this->response('', NO_CONTENT);
		}
		$this->response('', BAD_REQUEST);
	}

	private function addUser()
	{
		// Make sure that the request got here via POST.
		$this->checkMethod("POST");

		$email = 	$_POST['email'];
		$password = $_POST['pwd'];
		$type_id = 	(int)$_POST['type_id'];
		// echo $type_id; exit();

		// Validate Input
		if ( !empty($email) and !empty($password) and isset($type_id) )
		{
			if ( filter_var($email, FILTER_VALIDATE_EMAIL) )
			{
				$query = "INSERT INTO `users` (`email_address`, `user_type_id`, `password`) VALUES ('". $email . "', '" . $type_id . "', '" . md5($password) . "')";
				// Query that adds the ONE user record based the email address, name and (HASHED using MD5) password that was received in POST request.
				if( mysql_query($query, $this->db) )
				{
					$result = array('status' => "Success", 'msg' => "Successfully added user record.");
					$this->response($this->encode_data( $result ), OK);
				}else 
				{
					$result = array('status' => "Failed", 'msg' => "Could not add user record.");
					$this->response($this->encode_data( $result ), BAD_REQUEST);
				}
			}
		}
		// The last kind of response we'll give will be a bad request.
		// We'll give this response only if the caller doesn't call our method correctly ( i.e there's no email or password, or validation failed. )
		$error = array('status' => "Failed", 'message' => "Invalid Email Address, Name or Password");
		$this->response($this->encode_data($error), BAD_REQUEST); // BAD REQUEST: This is what you get if you don't format your API call correctly!!!
	}

	private function encode_data($data)
	{
		if (is_array($data))
		{
			return json_encode($data);
		}
	}
}

uriParser();
// Create a new instance of the API
$api = new API;
$api->processCall();

?>
