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
	//it runs in every 10 minutes
	public function order_assign()
	{
		//check today is holiday
		//if holiday no assign process
		$is_holiday = $this->attendance->checkHoliday();
		if($is_holiday){exit();}
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
			 //createduser_id = order ceated by staff or customer 
			 $createduser  = $order->createduser_id;
			 $cust_email   = $order->email;
			 $order_status = $order->order_status_id;
			 //processing order(id-2) assign only after 30 mnts from order placed
			 //else skip that order
			 if($order_status==2)
			 {
			   	//check time differnce more than 30 minuts 
				//else skip this processing order form auto asisgn rule
				$time_diffrence = $order->time_dif;
				if($time_diffrence < 30)
				{
				  continue;
				}
			 }
			 //get best users using assign rule
			  $assign_user  = $this->order->orderAssignUser($site_id,$order_id, $cust_email, $order_price,$site_code, $createduser, $order_status);
			 //asign order to user if get assigned user
			 if($assign_user)
			 {
			    $assign_user = strtolower($assign_user);
				$this->order->orderAssign($site_id, $order_id, $site_code, $assign_user, $order_status);
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
	
	//function for syncing reassign data from websites
	public function sync_order()
	{
	    //get all active sites for syncing
		$sites = $this->order->getAllSites();
		foreach($sites->result() as $site)
		{
		   $site_id   = $site->site_id;
		   $site_code = $site->site_code;
		   //get reassigned orders of this site 
		   $re_assigned_orders = $this->order->getAllReAssignOrder($site_code,$site_id);
		   foreach($re_assigned_orders as $order_id => $user_name)
		   {
		       //update username to new_username in assign_orders table
			    $this->order->changeReorderUser($order_id, $user_name, $site_id);
		   }
		}
		
		//remove test/trash order from order assign table. 
		//at the time of automated assign these ordrs are not in test/trash
		 //get all active sites for syncing
		$sites = $this->order->getAllSites();
		foreach($sites->result() as $site)
		{
		   $site_id   = $site->site_id;
		   $site_code = $site->site_code;
		   //get reassigned orders of this site 
		   $new_test_orders = $this->order->getAllTestOrders($site_code,$site_id);
		   foreach($new_test_orders->result() as $test_order)
		   {
		        $order_id = $test_order->order_id;
			   //delete order from order assign table
			    $this->order->deleteOrderAssign($order_id,$site_id);
		   }
		}
	}
	
	//sync full assign orders to opas table , last month orders
	public function syncing()
	{
	    //$sites = $this->opasa->query("select site_id,site_code from sites where site_id =1");
		$sites = $this->order->getAllSites();
		foreach($sites->result() as $site)
		{
		   $site_id     = $site->site_id;
		   $site_code   = $site->site_code;
		   $all_orders  = $this->order->getAllordersBySite($site_id,$site_code);
		   //check one by one, these order is in assign_orders table
		   foreach($all_orders->result() as $orders)
		   {
		      $order_id     = $orders->order_id;
			  $username     = $orders->username;
			  $dateadded    = $orders->date_added;
			  $order_status = $orders->order_status_id;
			  $this->order->syncOrder($site_id,$order_id,$username,$dateadded,$order_status);
		   }
		}
		
		//remove deleted procesisng order from directpr databse
		$sites = $this->order->getAllSites();
		foreach($sites->result() as $site)
		{
		   $site_id     = $site->site_id;
		   $site_code   = $site->site_code;
		   $proc_orders  = $this->order->get_daily_processing_order($site_id);
		   //check one by one, these order still exist in sites
		   foreach($proc_orders->result() as $proc_order)
		   {
		      $order_id     = $proc_order->order_id;
			  //check order is exist , else remove from table
			  $this->order->check_exist_in_site($site_code,$order_id,$site_id);
		   }
		}
	}
	
	//cron job for create repaet holidays
	//run every jan 1
	public function repeat_holiday()
	{
	    //$sql  = "select * from holidays where nextyear =1"
	}
	
	public function servertime()
	{
	   echo date('h:i:sa');
	}
	
	//cron job for assign free sample orders
	public function freesample_assign()
	{
	   $this->load->model('freesample_model','sample'); 
	   //check today is holiday
		//if holiday no assign process
		$is_holiday = $this->attendance->checkHoliday();
		if($is_holiday){exit();}
		//check todays attendance is marked
		//else create attendance with full present
		$is_attendance = $this->attendance->checkAttendance(date('Y-m-d'));
		if(!$is_attendance){$this->attendance->markDefaultAttendance();}
		//assign orders from all sites . one by one 
		//slecet all  site_id which have fee sample option; and corresponding database from sites table
		$sites = $this->sample->getFreeSampleSites();
		foreach($sites->result() as $site)
		{
		  $site_name = $site->site_name;
		  $site_id   = $site->site_id;
		  $site_code = $site->site_code;
		  //fetch all not assigned free sampleorders from site
		  $samples    = $this->sample->getAllNotAssignFreeSamples($site_code);
		  foreach($samples->result() as $sample)
		  {
		     $sample_id    = $sample->free_sample_id;
			 $product_id   = $sample->product_id;
			 //get best users using assign rule
			 $assign_user  = $this->sample->sample_order_assign_user($site_id,$sample_id);
			 //asign order to user if get assigned user
			 if($assign_user)
			 {
			    $assign_user = strtolower($assign_user);
				$this->sample->sample_order_assign($site_id, $sample_id, $product_id, $assign_user);
			 } 
			 
		  }//end orders
		}//end sites
	}
}
