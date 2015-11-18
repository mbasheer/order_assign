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

}

