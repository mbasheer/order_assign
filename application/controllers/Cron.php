<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Cron extends CI_Controller {

	/**
	 * Cron job controller.
	 * all cron job functions are defined here 
	
	 */
	//constructor function
	//runs this controller loads 
	public function __construct()
    {
         parent::__construct();
          // Your own constructor code
		  // load order_model database
		  $this->load->model('order_model','order');
		  $this->load->model('attendance_model','attendance');
		  $this->load->helper('basic');
    } 
	//cron job for assign orders automatically
	//it runs in every 15 minutes
	public function order_assign()
	{
		//check todays attendance is marked
		//else create attendance with full present
		$is_attendance = $this->attendance->checkAttendance(date('Y-m-d'));
		if(!$is_attendance){$this->attendance->markDefaultAttendance();}
		//assign orders from all sites . one by one 
		//slecet all site_id and corresponding database from sites table
		$sites = $this->order->getAllSites();
		$not_assigned = array();//not assigned orders(no best assined user for this order) are added to this array with site code
		foreach($sites->result() as $site)
		{
		  $site_name = $site->site_name;
		  $site_id   = $site->site_id;
		  $site_code = $site->site_code;
		  //fetch all not assigned orders from site
		  $orders    = $this->order->getAllNotAssignOrder($site_code);
		  foreach($orders->result() as $order)
		  {
		     $order_id     = $order->order_id;
			 $order_price  = $order->total;
			 //get best users using assign rule
			  $assign_user  = $this->order->orderAssignUser($site_id,$order_id,$order_price,$site_code);
			 //asign order to user if get assigned user
			 if($assign_user)
			 {
			    $assign_user = strtolower($assign_user);
				$this->order->orderAssign($site_id,$order_id,$site_code,$assign_user);
			 } 
			 //send email alert to admin while no user for any order
			 else
			 {
			    $not_assigned[] = array("order_id" => $order_id, "site_code" => $site_code, "order_price" => $order_price);
			 } 
		  }//end orders
		}//end sites
		
		//if any un assigned order after cone job run send an email alert with order details
		if(count($not_assigned) > 0)
		{
		   $data['not_assigned'] = $not_assigned;
		   $msg = $this->load->view('alert_mail',$data,TRUE);
		   //get alert email address from database
		   //this email id as the to address in this mail 
		   $alert_email      = $this->order->getSettings('alert_email');
		   
		   $this->load->library('email');
           $this->email->from('info@directpromotionals.com', 'Opas');
           $this->email->to($alert_email);
           $this->email->subject('Order assign rule alert');
           $this->email->message($msg);
           $this->email->send();
		}
	}
	public function servertime()
	{
	   echo date('h:i:sa');
	}
}
