<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Freesample_model extends CI_Model {
	public $site_db;
	public function __construct()
    {
         parent::__construct();
           // Your own constructor code
		 $CI = &get_instance();
         //setting the second parameter to TRUE (Boolean) the function will return the database object.
	     //loading default databse
         $this->opasa = $CI->load->database('hr', TRUE);
    } 
    public function getFreeSampleSites()
	{
	   $sql  = "select * from sites where sample_status = 1 order by site_id";
	   return $this->opasa->query($sql);
	}
	
	//latest unasigned free sample
	public function getAllNotAssignFreeSamples($site_code)
	{
	    $CI = &get_instance();
		$this->site_db = $CI->load->database($site_code, TRUE);
	    $sql = "SELECT `free_sample_id`,`product_id` FROM `sample_request_orders` WHERE 
		       (`assigned_to`=0 or `assigned_to` IS NULL) and `date_added` > DATE_SUB(NOW(), INTERVAL 10 DAY)";
		return $this->site_db->query($sql);
	}
	
	//get available user from free sample repo
	public function sample_order_assign_user($site_id,$sample_id)
	{
	    $best_user_first  = "SELECT a.username,a.site_id,a.`per_month`,a.`rule_priority`,b.present from order_assign_rule a 
                            join attendance b on a.username = b.username 
                            where b.work_date = CURDATE() and a.site_id = $site_id 
							and a.rule_type = '3'
							order by a.username desc"; 						  
	    $sql_best_user_frst =  $this->opasa->query($best_user_first);
		//if no result assign to lead_repo user
		if($sql_best_user_frst->num_rows() < 1) //if no user match the rule
		{
		    //get lead repo
			$assigned_user = $this->getLeadRepo($site_id);
		}
		else
		{
		     //check that users limits the daily order permit
		     //remove  user who cross the daily limit
		     $users_after_daily_check = $this->filterDailyLimit($sql_best_user_frst); //array
			 //if no best user , check if any lead repo
			 if($users_after_daily_check == 0)
			 {
			    $assigned_user = $this->getLeadRepo($site_id);
			 } 
			 //if array contain only one user set that user as $assigned_user
			 //else solve conflict
			 else if(count($users_after_daily_check)==1)
			 {
			    $assigned_user = array_shift($users_after_daily_check);
			 }
			 else
			 {
			    $assigned_user = $this->solveConflict($users_after_daily_check,$site_id);
			 }
		}
		
		return $assigned_user;
	}
//get lead repo of site
	public function getLeadRepo($site_id)
	{
	       //check more than one lead repo
		   //if yes slect best usernam efrom that
		     $best_lead_repo      = "select a.username from order_assign_rule a where a.lead_repo = 1 and a.site_id = $site_id and a.rule_type = '3'";
			 $sql_best_lead_repo  = $this->opasa->query($best_lead_repo);
			 if($sql_best_lead_repo->num_rows() == 1) //if only one user match the rule
		     {
		        $best_user_row = $sql_best_lead_repo->row();
			    $assigned_user = $best_user_row->username;
			    //$this->assign($site_id,$order_id,$assigned_user);
		     }
			 //conflict 
		     else if($sql_best_lead_repo->num_rows() > 1)
		     {
		       //convert to array
			   $user_array = array();
			   foreach($sql_best_lead_repo->result() as $user)
			   {
			     $user_array[] = $user->username;
			   }
			   $assigned_user = $this->solveConflict($user_array,$site_id);
		     }
			 else
			 {
			    $assigned_user = 0;
			 }
			 return $assigned_user;
	}
	
	//input- sql result with username and this month assigned order count and monthly limit
	//return array result after filter
	public function filterDailyLimit($sql)
	{
	   loop :$ret_array = array();
	        
			//if no result retun 0;
			if($sql->num_rows()<1)
			{
			  return 0;
			}
			foreach($sql->result() as $user)
			{ 
			   $priority   = 0;  //initially priority set as o , 
			   $username   = $user->username;
			   $permonth   = $user->per_month; // avail quata
			   $site_id    = $user->site_id;
			   $rule_priority = $user->rule_priority;
			   $user_present  = $user->present; 
			   //if user have larger priority than current priority then priority set to larger one
			   if($rule_priority > $priority){$priority = $rule_priority;}
			   
			   //check is absent
			   if($user_present == 0){continue;}
			   //no limittaion or threshold add to array
			   if($permonth=='' || $permonth ==NULL)
			   {
			      $ret_array[] = $username;
			   }
			   else 
			   {
			      //get monthly limit
				  $month_count     = "select count(*) as monthcount from sample_assign_orders 
				                      where username = '$username' and site_id = $site_id 
									  and MONTH(CURDATE())= MONTH(assign_date) AND YEAR(CURDATE())=YEAR(assign_date)";
				  $sql_month_count = $this->opasa->query($month_count);	
				  $row_month_count = $sql_month_count->row();
				  $month_count     = $row_month_count->monthcount;
				  //check this user exceeds the monthly limit
				  //if exceeds continue the for loop
				  if($permonth <= $month_count)
				  {
				    continue;
				  }
				  //then check per day limit exceeds
				  //get current day order count
				  $day_count     = "select count(*) as daycount from sample_assign_orders 
				                    where username = '$username' and site_id = $site_id and assign_date = CURDATE()";
				  $sql_day_count = $this->opasa->query($day_count);	
				  $row_day_count = $sql_day_count->row();
				  $day_count     = $row_day_count->daycount;	
				  //calculate daily limit from per_month
				  //check is that limit exceeds in current day
				  $remaining_days = countDays(date('y'), date('n'), date('j'),array(0, 6));
				  $daily_limit    = ceil(($permonth-$month_count)/$remaining_days);
				  //if exceeds the daily limit continue for loop
				  if($daily_limit <= $day_count)
				  {
				    continue;
				  }
				  //if usr have avail quata that username add to array		
				  $ret_array[] = $username; 
			   }//else end - no limitation, per_count is not defined
			}//for each end
		   //check atleast one user satisfy the rule 
		   //if $ret_array[] is null go to next level and repeat the loop.
		   if(count($ret_array) > 0)
		   {
		     return $ret_array;
		   }
		   else
		   {
		      //get next level users
			  $sql = "SELECT a.username,a.site_id,a.`per_month`,a.`rule_priority`,b.present from order_assign_rule a 
                      join attendance b on a.username = b.username 
                      where b.work_date = CURDATE() and a.site_id = $site_id 
					  and a.rule_priority = (select rule_priority from order_assign_rule where  rule_priority > $priority and site_id = $site_id and rule_type = '3' order by rule_priority limit 1)
					  and a.rule_type = '3'
					  ";		  
					
			 $sql  = $this->opasa->query($sql);	  
			 goto loop; 		  
		   }	
	}
	//if more than one users match the rule, we have to find out the best one
	//that user should have the oldest assigned order than others
	public function solveConflict($array,$site_id)
	{ 
	         //select one user from the list 
			 //and that user is assigned early tahn others
			 $username = ''; //username separated by ','
			 while($user = array_shift($array))
			 { 
			   $username.=',';
			   $username=$username.'"'.$user.'"';
			   $last_user = $user; // this variable for assigning random username below
			 }
			 $username = ltrim($username,',');
			 						 //get  oldest 'assign_orders' id  and corresponding username
			 $latest_assign     = "SELECT max(a.id),b.username FROM `sample_assign_orders` a 
			                       right join users b on a.username=b.username and a.site_id = $site_id
								   where b.username in (".$username.") group by b.username order by max(a.id) limit 0,1";
			 $sql_latest_assign = $this->opasa->query($latest_assign);
			 //if no result , selct random username, in this case last username
			 if($sql_latest_assign->num_rows() < 1)
			 {
			    $assigned_user  = $last_user;
			 }//end no result
			 else
			 {
			    $row_latest_assign = $sql_latest_assign->row();
				$assigned_user     = $row_latest_assign->username;
			 }
			 return $assigned_user;
	}
	
	
	//assign sample order to rep in websites table
	public function sample_order_assign($site_id, $sample_id, $product_id, $assign_user)
	{
 		 //select userid from  username from remote server database table
		 $query_user_id  = "SELECT user_id FROM `user` where LOWER(username) = '$assign_user'";
		 $sql_user_id    = $this->site_db->query($query_user_id);
		 if($sql_user_id->num_rows() == 1)
		 {
		     $row_user_id = $sql_user_id->row();
			 $user_id     = $row_user_id->user_id;
			 //update sample_request_orders table 
			 //customer_assign column in order table
			 $query_order_update = "update `sample_request_orders` set assigned_to = '$user_id' where free_sample_id = '$sample_id'";
			 
			 if($this->site_db->query($query_order_update))
			 {
			   //insert order assign details to master database  
			    $sql    = "INSERT INTO `sample_assign_orders` (`id`, `sample_id`, `product_id`, `site_id`, `username`, `assign_date`) 
				           VALUES (NULL, '$sample_id', '$product_id', '$site_id', '$assign_user', now())";
			    $this->opasa->query($sql);			   
			 }
			
		 }
	}
}	