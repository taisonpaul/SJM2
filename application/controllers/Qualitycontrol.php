
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Qualitycontrol extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
		$this->acl->hasAccess();
	}
	
    // to create new page
    // Step 1: create a function like line number: 11 (copy from line number 11 to 20 and create a new function to match pagename)
    // Step 2: Copy page the content of the function and change the content view between header and footer.
    // Step 3: Create a view file in corresponding folder and update the respective path here.
    public function reconciliationreport()
    {

        $this->load->view("templates/header.php");
        
        // update views/qualitycontrol/reconciliationreport.php file       
        $this->load->view("qualitycontrol/reconciliationreport");
        
        $this->load->view("templates/footer.php");
    }
    
}