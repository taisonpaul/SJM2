<?php
class Caspiotools
{
   private $SoapClient;
   private $AccountID = 'dwpp'; //caspio bridge account ID
   private $ProfileName = 'TIMMCPPWS'; //web services profile name
   private $Password = 'If2pTwxQKs2o'; //web services profile password
   
   function __construct() {
     
      $this->SoapClient = new SoapClient("https://c0ezh341.caspio.com/ws/api.asmx?wsdl"); //TODO: Make Bridge a passed parameter
      
   
   }
   
    /**
     * Function to get the account ID
     * 
     * @return string
     */
   public function getAccountId()
   {
       return $this->AccountID;
   }
   
   /**
    * Getter for the profilename
    * @return string
    */
   public function getProfileName()
   {
       return $this->ProfileName;
       
   }
   
   /**
    * getter for password
    * 
    * @return string
    */
   public function getPassword()
   {
        return $this->Password;    
   }
   
   //inserts data into a table
   public function insert($table, $values) {
      if($values == null) { //TODO remove this
         return $false;
      }
   
      $fields = '';
      $query_values = "";
      
      foreach($values as $field => $value) {
         $fields .= $field . ', ';
         $query_values .= "'{$value}'" . ", ";
      }
      $fields = substr($fields, 0, strlen($fields) - 2);
      $query_values = substr($query_values, 0, strlen($query_values) - 2);

      //TODO make this XML
      try {
         $insert_result = $this->SoapClient->InsertData($this->AccountID, $this->ProfileName, $this->Password, 
            $table, false, $fields, $query_values);
      } catch(SoapFault $e) {
         //TODO Elegantly handle failures
       
          throw $e;
         return false;
      }
      return $insert_result;
   }
   
   //fetches rows which match criteria, storing fields into associative array
   //returns null if no rows matched
   public function fetch($table, $fields, $criteria = '', $isView = false) {
      $query_fields = '';
      foreach($fields as $field) {
            $query_fields .= $field . ', ';
      }
      //TODO make this XML
      try {
        $select_result = $this->SoapClient->SelectDataRaw($this->AccountID, $this->ProfileName, $this->Password, 
            $table, $isView , $query_fields, $criteria, '', '$|CTLS$|', ' ');
      } catch(SoapFault $e) {
         //TODO Elegantly handle failures
          print_r($fields);
        echo $e->getMessage();
         return NULL;
      }
      
      if($select_result[0] == NULL) {
        return NULL;
      }

      for($i = 0; $i < count($select_result); $i++) {
         $row_values = explode('$|CTLS$|', $select_result[$i]);
         for($j = 0; $j < count($fields); $j++) {
            if(trim($row_values[$j]) === 'NULL') { //clean out NULL strings
               $row_values[$j] = NULL;
            }
            $results[$i]["{$fields[$j]}"] = trim($row_values[$j]);
         }
      }
      return $results;
   }
   
   //updates data in a table, values is an associative array of field-value pairs
   //returns number of columns updated
   public function update($table, $values, $criteria) {
      if($values == null) { //TODO remove this
         return $false;
      }
   
      $fields = '';
      $query_values = "";
      
      foreach($values as $field => $value) {
         $fields .= $field . ', ';
         $query_values .= "'{$value}'" . ", ";
      }
      $fields = substr($fields, 0, strlen($fields) - 2);
      $query_values = substr($query_values, 0, strlen($query_values) - 2);

      //TODO make this XML
      try {
         $update_result = $this->SoapClient->UpdateData($this->AccountID, $this->ProfileName, $this->Password, 
            $table, false, $fields, $query_values, $criteria);
      } catch(SoapFault $e) {
         //TODO Elegantly handle failures
        echo $e->getMessage();
         return false;
      }
      return $update_result;
   }
   
   //deletes matching items from specified table
   //returns number of rows affected
   public function delete($table, $criteria) {
      //TODO make this XML
      try {
         $delete_result = $this->SoapClient->DeleteData($this->AccountID, $this->ProfileName, $this->Password, 
            $table, false, $criteria);
      } catch(SoapFault $e) {
         //TODO Elegantly handle failures
         return false;
      }
      return $delete_result;
   }
   
   //check if user is logged in
   public function logged_in() {
      if(isset($_SESSION) && isset($_SESSION['userinfo'])) {
         return true;
      } else {
         return false;
      }
   }
   
   //returns true on successful login, false on non
   public function login($username, $password, $table, $username_field, $password_field, $userinfo_fields) {
      if($this->logged_in()) { //already logged in!
         return true;
      }
      
      $params = array(
         'AccountID' => $this->AccountID, 
         'Profile' => $this->ProfileName,
         'Password' => $this->Password,
         'ObjectName' => $table,
         'IsView' => false,
         'PasswordFieldName' => $password_field,
         'PasswordValue' => $password,
         'Criteria' => "$username_field='{$username}'",
         'OrderBy' => ''
      );
      
      $loggedin = false;
      while(!$loggedin) { //keep trying incase CheckPassword is timed out
         try {
            $login_result = $this->SoapClient->CheckPassword($params);
            $loggedin = true;
         } catch(SoapFault $e) {
            if($e->faultstring === '{CheckPassword API cannot be called at this time.}') {
               //sleep(5); //wait 5 seconds and try again
            } else {
               //TODO Elegantly handle failures
               return false;
            }
         }
      }
      if(isset($login_result->CheckPasswordResult->Row)) { //user logged in successfully
         //grab their userinfo now
         $fields = '';
         foreach($userinfo_fields as $field) {
            $fields .= $field . ', ';
         }
         
         try {
            $select_result = $this->SoapClient->SelectDataRaw($this->AccountID, $this->ProfileName, $this->Password, 
               $table, false, $fields, "$username_field='{$username}'", '', '$|CTLS$|', ' ');
         } catch(SoapFault $e) {
            //TODO Elegantly handle failures
            return false;
         } 

         $field_values = explode('$|CTLS$|', $select_result[0]);
         for($i = 0; $i < count($userinfo_fields); $i++) {
            $userinfo["{$userinfo_fields[$i]}"] = trim($field_values[$i]);
         }
         
         $_SESSION['userinfo'] = $userinfo;
         return true;
      } else { //incorrect username/password
         //TODO handle failed login
      }
   }
   
   //logs the user out, must be called before content is sent (with headers)
   public function logout() {
      // Unset all of the session variables, cookie, and destroy session
      $_SESSION = array();
      if (ini_get("session.use_cookies")) {
         $params = session_get_cookie_params();
         setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
         );
      }
      session_destroy();
   }
   
   //returns userinfo for logged in user, else null if no logged in user
   public function user() {
     if($this->logged_in()) {
       return $_SESSION['userinfo'];
     } else {
       return null;
     }
   }
}
?>