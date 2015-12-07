<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Attendance_model extends CI_Model {
	public function __construct()
    {
	     parent::__construct();
           // Your own constructor code
		 $CI = &get_instance();
         //setting the second parameter to TRUE (Boolean) the function will return the database object.
	     //loading default databse
         $this->opasa = $CI->load->database('hr', TRUE);
	}
	
	
	public function getLogin($user_name,$password)
	{
	     $username = $this->opasa->escape_str($user_name);
	     $sql  = "select * from admin where username = '$user_name' and password = '$password'";
		 return $this->opasa->query($sql);
	}
	//check attendance is marked on date
	//is amarked return 1, else return 0
	public function checkAttendance($date)
	{
	    $sql = "select id from attendance where work_date = '$date'";
		$sql = $this->opasa->query($sql);
		if($sql->num_rows() > 0)
		{
		  return 1;
		}
		else
		{
		  return 0;
		}
	}
	
	//mark attendance with full presnet in today date
	public function markDefaultAttendance()
	{
	    $sql_all = $this->opasa->query("select username from users where username <> 'blank' order by name");
		$values ='';
		foreach($sql_all->result() as $row)
		{
		  $values.=",(NULL,'".$row->username."',NOW(),1,0,CURTIME())";
		}
		$insert_value = ltrim($values,',');
		$sql = "insert into attendance values ".$insert_value;
		$this->opasa->query($sql);
	}
	
	//get username form attendance table , 
	function getTodayUsers($present=1)
	{
	    $sql  = "select a.name,b.username,b.present,b.work_date from users a join attendance b on a.username = b.username 
		         where b.present = $present and b.work_date = CURDATE() order by a.name";
		return $this->opasa->query($sql);
	}
	
	public function updateAttendanceTime()
	{
	    $user_id = $this->session->userdata('user_id');
		$sql = "update attendance set updated_by = $user_id ,last_update = CURTIME() where work_date = CURDATE()";
		$this->opasa->query($sql);
	}
	
	//mark absent 
	public function markAbsent($user)
	{
	    $user_id = $this->session->userdata('user_id');
	    $sql = "update attendance set present = 0, updated_by = $user_id ,last_update = CURTIME() 
		        where work_date = CURDATE() and username = '$user'";
		$this->opasa->query($sql);		
	}
	//mark absent 
	public function markPresent($user)
	{
	    $user_id = $this->session->userdata('user_id');
	    $sql = "update attendance set present = 1, updated_by = $user_id ,last_update = CURTIME() 
		        where work_date = CURDATE() and username = '$user'";
		$this->opasa->query($sql);		
	}
	//get attendanc mark restrict time
	public function getAttendanceRestrictTime()
	{
		$admin_id = $this->session->userdata('user_id');
		$sql      = "select a.attendance_time_limit from admin_type a join admin b on a.type_id = b.type where admin_id = $admin_id";
		$sql = $this->opasa->query($sql);
		$row = $sql->row();
		return $row->attendance_time_limit;
	}
	
	//get all sites: condition -> this is the only one user for this sites available for today nd all othesr are absent or only one user is assigned for this site
	public function getSites_only_oneuser($user_name)
	{
		$query_sites = "select count(a.username) as users,c.site_code,a.username from order_assign_rule a join attendance b on a.username = b.username join sites c on a.site_id = c.site_id where b.present =1 and b.work_date = CURDATE() and a.site_id in(select site_id from order_assign_rule where username = '$user_name') group by a.site_id having users = 1";
		$sql_sites   = $this->opasa->query($query_sites);
		return $sql_sites;
		
	}
	
	//insert to holiday table and return last insrt id
	public function addNewHoliday()
	{
	   $admin_id     = $this->session->userdata('user_id');
	   $holiday_date = $_REQUEST['holiday_date'];
	   $subject      = $_REQUEST['subject'];
	   $repeat       = $_REQUEST['repeat'];
	   $sql          = "INSERT INTO `holidays` (`id`, `holiday_date`, `subject`, `nextyear`, `created_by`, `updated_date`) 
	                    VALUES (NULL, '$holiday_date', '$subject', '$repeat', '$admin_id', NOW())";
	   $this->opasa->query($sql);	
	   return $this->opasa->insert_id();				
	}
	
	public function getHolidaybyId($holiday_id)
	{
	   $sql  = "select * from holidays where id = '$holiday_id'";
	   return $this->opasa->query($sql);
	}
	
	public function getHolidayList()
	{
	   $sql = "select * from holidays where holiday_date >= CURDATE() order by holiday_date";
	   return $this->opasa->query($sql);
	}
	//delete holiday
	public function delete_holiday($id)
	{
	   $sql  = "delete from holidays where id = '$id'";
	   $this->opasa->query($sql);
	}
	public function updateHoliday($holiday_id)
	{
	   $holiday_date = $_REQUEST['holiday_date'];
	   $subject      = $_REQUEST['subject'];
	   $sql          = "update holidays set holiday_date = '$holiday_date', subject = '$subject',updated_date=NOW()  where id ='$holiday_id'";
	   $this->opasa->query($sql);
	}
	
	public function checkHoliday()
	{
	   $sql = $this->opasa->query("select * from holidays where holiday_date = CURDATE()");
	   if($sql->num_rows() > 0)
	   {
	     return 1;
	   }
	   else return 0;
	}
}	