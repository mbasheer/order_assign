<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
//attendance controller
class Attendance extends CI_Controller {
     //constructor function
	//runs this controller loads 
	public function __construct()
    {
        parent::__construct();
          // Your own constructor code
		$this->load->model('attendance_model','attendance');  
		$this->load->model('order_model','order');  
    } 
	//attendance home 
	public function index()
	{
	   $data['msg'] = '';
	   //if no session redirect to login page
	   if(!$this->session->has_userdata('user_id'))
	   {
	      redirect(base_url().'index.php/attendance/login');
	   }
       //get restric time if any for this admin user
	   $restrict_time = $this->attendance->getAttendanceRestrictTime();
	   $current_time  = date('H:i:s');
	   //if restricted time on set restrict flag as 1
	   $data['restrict'] = 0;
	   if($restrict_time != '00:00:00' && $current_time > $restrict_time)
	   {
		    $data['restrict'] = 1;
	   }
	   
	  if(isset($_POST['save']))
	  {
		//absent mark restricted after some fixed morning time for admins
		//check if any restriction for this admin
		if($restrict_time != '00:00:00' && $current_time > $restrict_time)
		{
		    $data['msg'] = 'Attendance Marking Restricted!';
		}
		else // no restriction or under restriction time
		{   
			//present marking
			if(isset($_POST['present']))
			{
			  $present=$_POST['present'];
			  //mark absent 
			  //and re assign thier order to others using rule
			  foreach($present as $user)
			  {
				 $this->load->model('order_model','order');
				 //update attendance table , present 1
				 $this->attendance->markPresent($user);
				 $data['msg'] = 'Attendance marked successfully';
			  } 
			}
			 //absent marking
			if(isset($_POST['absent']))
			{
			  $absent=$_POST['absent'];
			  //mark absent 
			  //and re assign thier order to others using rule
			  foreach($absent as $user)
			  {
				 $this->load->model('order_model','order');
				 //delete all assign_orders , where username = user and assign_date = current date , and return delted order id and site_id
				 //get assign_orders details of that user
				 //update attendance table , present 0
				 $this->attendance->markAbsent($user);
				 $deleted_orders  = $this->order->getTodayOrdersByUser($user);
				 if($deleted_orders->num_rows() > 0)
				 {
				   //update remote site order table
				   //customer_assign to 0 
					foreach($deleted_orders->result() as $order)
					{
					   $id       = $order->id;
					   $site_id  = $order->site_id;
					   $order_id = $order->order_id;
					   $site_code= $order->site_code;
					   $this->order->updateAssignToZero($id, $site_id, $order_id, $site_code); 
					}
				 }
				 $data['msg'] = 'Attendance marked successfully and re-assign orders';
			  } 
			}
		}
	  } //end isset save
	   //get all present users
	  $data['present_users'] = $this->attendance->getTodayUsers(1);
	  $data['absent_users'] = $this->attendance->getTodayUsers(0);
	  //data for assign rule tab
	  $data['employee']     = $this->order->getUsersList();
	  $data['sites']        = $this->order->getSiteList();
	  $data['rules']        = $this->order->getRuleList();
	  $data['holidays']     = $this->attendance->getHolidayList();
	  $this->load->view('attendance_mark',$data);
	}
	//login view
	public function login()
	{
	   $this->load->library('form_validation');
	   //if session_id redirect to home page
	   if($this->session->has_userdata('user_id'))
	   {
	      redirect(base_url().'index.php/attendance');
	   }
	   
	   $data['msg'] = '';
	   //after login button clicks
	   if(isset($_POST['username']))
	   {
	       //checks login credential is correct or not
		   $user_name    = $this->input->post('username');
		   $password     = md5($this->input->post('password'));
		   $log_result   = $this->attendance->getLogin($user_name,$password);
		   //check any result with this login details
		   //yes, create session and redirect to home page , otherwise print error message
		   if($log_result->num_rows() == 1)
		   {
		      $row = $log_result->row();
			  $this->session->set_userdata('user_id',$row->admin_id);
			  $this->session->set_userdata('admin_name',$row->name);
			  $this->session->set_userdata('user_type',$row->type);
			  redirect(base_url().'index.php/attendance');
		   }
		   else
		   {
		      $data['msg'] = 'Invalid Login Details !';
		   }
	   }
	   $this->load->view('login_view',$data);
	}
	
	//logout
	public function logout()
	{
	   $this->session->sess_destroy();
	    redirect(base_url().'index.php');
	}
	
	//check user is one and only one repo for any site
	//return sites names
	//else return 0
	public function check_unique_user()
	{
	   $user_name = $_POST['selected_user'];
	   $name = $_POST['name'];
	   $sites     = $this->attendance->getSites_only_oneuser($user_name);
	   if($sites->num_rows() < 1)
	   {
	      echo 0;
	   }
	   else
	   {
	     $msg = 'The Associate handles multiple products alone, so please contact the Administrator';
		 foreach($sites->result() as $site)
		 {
		   $site_name = $site->site_code;
		   $username = $site->username;
		   if($username != $user_name)
		   {
		     echo 0;return;
		   }
		   
		 }
		 echo $msg;
	   }
	}
	
	//add new holiday
	public function mark_holiday()
	{
	    $holiday_id = $this->attendance->addNewHoliday();
		//if successfully insert the holiday
		//create html 
		if($holiday_id)
		{
		    $rule_result  = $this->attendance->getHolidaybyId($holiday_id);
			$data['holiday']  = $rule_result->row();
			//new rule row view
			$this->load->view('new_holiday_row',$data);
		}
	}
	
	//function delete holiday
	public function delete_holiday()
	{
	    if(isset($_GET['delete'])) 
		{
		   $holiday_id = $_GET['delete'];
		   $this->attendance->delete_holiday($holiday_id);
		}
	}
	
	//get edit form by holiday id
	public function get_edit_form()
	{
	    if(isset($_REQUEST['holiday_id'])) 
		{
		   $holiday_id = $_REQUEST['holiday_id'];
		   $holiday_result       = $this->attendance->getHolidaybyId($holiday_id);
		   $data['holiday']      = $holiday_result->row();
		   $this->load->view('edit_holiday_form',$data);
		}
	}
	
	//edit holiday form
	function edit_holiday()
	{
	    //update holiday
		$holiday_id  = $_REQUEST['holiday_id'];
		$this->attendance->updateHoliday($holiday_id);
		//create revised html with new changes 
		$holiday_result  = $this->attendance->getHolidaybyId($holiday_id);
		$data['holiday']  = $holiday_result->row();
		//new rule row view
		$this->load->view('updated_holiday_row',$data);
		
	}

}

