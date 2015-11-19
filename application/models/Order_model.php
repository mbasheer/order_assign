<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Order_model extends CI_Model {
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
	//cron job for assign orders automatically
	//it runs in every 15 minutes
	public function getAllSites()
	{
		//select all sites their status =1 from master db
		$sql = "SELECT * FROM `sites` where status = 1 order by priority";
		return  $this->opasa->query($sql);
	}
	
	//get all un assigned order id from individual sites
	//@site_code - corresponding shopping site code and database connection name
	public function getAllNotAssignOrder($site_code)
	{
	    $CI = &get_instance();
		$this->site_db = $CI->load->database($site_code, TRUE);
		//get order_ids
		//avoid old orders
		//select only from one week time frame
		
		//this ithe correct sql, commented only for testing 
		$sql  = "SELECT `order_id`,`date_added`, total, createduser_id FROM `order` 
		         WHERE (`customer_assign` = 0 or customer_assign IS NULL) AND (date_added > DATE_SUB(NOW(), INTERVAL 1 WEEK) or date_modified > DATE_SUB(NOW(), INTERVAL 1 WEEK)) 
				 AND order_status_id not in(2,45)
				 order by date_added";	
		/*$sql  = "SELECT `order_id`,`date_added`, total FROM `order` 
		         WHERE (`customer_assign` = 0 or customer_assign IS NULL)
				 order by date_added desc limit 2";	 */
		return $this->site_db->query($sql); 
	}
	
	//return assigned username
	//input - site id , order id and price
	public function orderAssignUser($site_id,$order_id,$order_price,$site_code,$createduser)
	{
	    $assigned_user = 0;
		//if order is blank that order directly assigned to blank user with our any further checking
		//call checkorderisblank function for checking this orsder is balnk or not
		//return 1 is blank otherwise return 0
		$is_blank = $this->checkOrderIsBlank($site_code,$order_id);
		if($is_blank)
		{
		   
		   return 'blank';
		}
		
		//check this order created by our staff other than customer assign to that staff 
		if($createduser != 0)
		{
		   //if any created user id
		   //gt that username from user_id
		   $username = $this->getUsernamefromId($createduser);
		   if($username){return $username;}
		}
		
		
		//if this order is a re-order(copy) of another order , assigned to same user
		//return parent order assigned user , if copied order
		//else return 0
		$is_copy  = $this->checkCopiedOrder($order_id);
		if($is_copy)
		{
		   //check this username is present today
		   //otherwise this order act as a normal order 
		   $present_status_sql = $this->opasa->query("select present from attendance where username = '$is_copy' and work_date = CURDATE()");
		   $present_status_row = $present_status_sql->row();
		   $present_status     = $present_status_row->present;
		   //copied userr is presnet return 
		   if($present_status == 1)
		   {
		     return $is_copy; //parent order assigned username
		   }	 
		}
		
		//first we have to find which user is best for this order from order assign rule
		//then check that user is not exceeds the threshold
		//threshold - max order per month where MONTH(CURDATE())=MONTH(assign_date)
		//first round chek , 
		//get all user satisfy the assign rule with this order id 
		/* $best_user_first  = "select count(a.order_id) as ordercount,b.username,b.per_month,b.rule_priority,b.site_id,c.present 
		                       from order_assign_rule b
		                          left join assign_orders a 
							      ON a.username = b.username AND MONTH(CURDATE())= MONTH(assign_date) AND YEAR(CURDATE())=YEAR(assign_date) 
						          and a.site_id = $site_id
								  join attendance c on  b.username = c.username and c.work_date = CURDATE()
		                          where b.site_id = $site_id AND ((b.max_order_amount > $order_price AND b.min_order_amount <= $order_price) 
							      OR (b.max_order_amount = '' AND b.min_order_amount <= $order_price))
							      group by b.username,b.per_month,b.rule_priority,b.site_id,c.present";*/
								  
		$best_user_first  = "SELECT a.username,a.site_id,a.`per_month`,a.`rule_priority`,b.present from order_assign_rule a 
                            join attendance b on a.username = b.username 
                            where b.work_date = CURDATE() and a.site_id = $site_id 
							and( 
                                (a.max_order_amount > $order_price AND a.min_order_amount <= $order_price) 
                             OR (a.max_order_amount = '' AND a.min_order_amount <= $order_price)) 
							 order by a.username desc"; 						  
	    $sql_best_user_frst =  $this->opasa->query($best_user_first);
		//if no result assign to lead_repo user
		if($sql_best_user_frst->num_rows() < 1) //if no user match the rule
		{
		    //check more than one lead repo
		   //if yes slect best usernam efrom that
		     $best_lead_repo      = "select a.username from order_assign_rule a join attendance b on a.username = b.username 
			                         where a.lead_repo = 1 and a.site_id = $site_id and b.work_date = CURDATE() and b.present = 1";
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
		}
		else
		{
		     //check that users limits the daily order permit
		     //remove  user who cross the daily limit
		     $users_after_daily_check = $this->filterDailyLimit($sql_best_user_frst); //array
			 if($users_after_daily_check == 0){return 0;} // no result return 0;
			 //if array contain only one user set that user as $assigned_user
			 //else solve conflict
			 if(count($users_after_daily_check)==1)
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
				  $month_count     = "select count(*) as monthcount from assign_orders 
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
				  $day_count     = "select count(*) as daycount from assign_orders 
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
					  and a.rule_priority = (select rule_priority from order_assign_rule where  rule_priority > $priority and site_id = $site_id order by rule_priority limit 1)";		  
					
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
			 $latest_assign     = "SELECT max(a.id),b.username FROM `assign_orders` a 
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
	
	//assign order 
	public function orderAssign($site_id,$order_id,$site_code,$username)
	{    
	     $CI = &get_instance();
		 $this->site_db = $CI->load->database($site_code, TRUE);
		 //select userid from  username from remote server database table
		 $query_user_id  = "SELECT user_id FROM `user` where LOWER(username) = '$username'";
		 $sql_user_id    = $this->site_db->query($query_user_id);
		 if($sql_user_id->num_rows() == 1)
		 {
		     $row_user_id = $sql_user_id->row();
			 $user_id     = $row_user_id->user_id;
			 //update order table 
			 //customer_assign column in order table
			 $query_order_update = "update `order` set customer_assign = $user_id where order_id = '$order_id'";
			 
			 if($this->site_db->query($query_order_update))
			 {
			   //insert order assign details to master database  
			    $sql    = "INSERT INTO `assign_orders` (`id`, `username`, `site_id`, `order_id`, `assign_date`) 
				           VALUES (NULL, '$username', '$site_id', '$order_id', NOW())";
			    $this->opasa->query($sql);			   
			 }
			
		 }
 	}
	
	//get all today assigned oder and siteid of a particular user 
	public function getTodayOrdersByUser($username)
	{
	    $sql = "select a.id, a.site_id, a.order_id, b.site_code from assign_orders a join sites b
		        on a.site_id = b.site_id where a.username = '$username' and a.assign_date = CURDATE()";
		return $this->opasa->query($sql);
	}
	
	//update remote customer_assign field to zero (0)
	public function updateAssignToZero($id, $site_id, $order_id, $site_code)
	{
	     $CI = &get_instance();
		 $this->site_db = $CI->load->database($site_code, TRUE);
		 $query_order_update = "update `order` set customer_assign = 0 where order_id = '$order_id'";
		 if($this->site_db->query($query_order_update))
		 {
			 //delete order assign details to master database  
			  $sql    = "delete from assign_orders where id = '$id'";
			  $this->opasa->query($sql);			   
		 } 
	}
	
	//check order is blank (no imprint)
	//cmd,magnetsbudy and CND - order_product table , label = blank 
	//all other sites order_product table - isblank column - 2 
	//if all product in that order is blank , then order is blank
	//if any one product has iprint that product is not a blank 
	public function checkOrderIsBlank($site_code,$order_id)
	{
	   //first check this order comes from which site
	   $blank_arr  = array('CMD','CND','MBY');
	   if(in_array($site_code,$blank_arr))
	   {
	      //check if any product have imprinte 
		  $query_product  = "select product_id from order_product where order_id = '$order_id' and label <> 'Blank'";
	   }
	   else
	   {
	      $query_product  = "select product_id from order_product where order_id = '$order_id' and isblankproduct <> 2";
	   }
	   
	   $sql_product = $this->site_db->query($query_product);
	   //if result that order is not blank
	   //return 0
	   if($sql_product->num_rows()>0)
	   {
	      return 0;
	   }
	   else
	   {
	      return 1;
	   }
	}
	
	//check order is copied from another order
	//if yes rturn parent order assigned username
	//else return 0
	function checkCopiedOrder($order_id)
	{
	   //get order history comment of order and check 
	   $query_copy_comment = "SELECT comment FROM `order_history` WHERE lower(`comment`) LIKE 'copied from%' and order_id = '$order_id' ORDER BY `date_added` limit 1";
	   $sql_copy_comment   = $this->site_db->query($query_copy_comment);
	   if($sql_copy_comment->num_rows()<1)
	   {
          //if no copied from comment return 0
		  return 0;	   
	   }
	   $row_copy_comment   = $sql_copy_comment->row();
	   $copy_comment       = $row_copy_comment->comment; // 	Copied from SGV15051804
	   $parent_order_id    = trim(str_replace('Copied from ','',$copy_comment)); // SGV15051804
	   //get parent order's assigned username
	   $query_assigned     = "select a.username from `user` a join `order` b on b.customer_assign = a.user_id where b.order_id = '$parent_order_id' ";
	   $sql_assigned       = $this->site_db->query($query_assigned);
	   //if no username
	   if($sql_assigned->num_rows() < 1)
	   {
	      return 0;
	   }
	   $row_assigned       = $sql_assigned->row();
	   return $row_assigned->username;
	}
	
	//return usernmae from user_id
	function getUsernamefromId($createduser)
	{
	    $query_user  = "SELECT `username` FROM `user` WHERE `user_id`='$createduser'";
		$sql_user    = $this->site_db->query($query_user);
		if($sql_user->num_rows() < 1)
		{
		  return 0;
		}
		$row_user = $sql_user->row();
		return $row_user->username;
	}
	
	//function get all users
	public function getUsersList()
	{
	    $sql = "SELECT * FROM `users` ORDER BY `name`";
		return $this->opasa->query($sql);
	}
	//function get all sites
	public function getSiteList()
	{
	    $sql = "SELECT * FROM `sites` ORDER BY `site_name`";
		return $this->opasa->query($sql);
	}
	//get all available rules
	public function getRuleList()
	{
	    $sql = "select a.*,b.site_code,b.site_name,c.name from order_assign_rule a 
		        join sites b on a.site_id = b.site_id join users c on a.username = c.username order by b.site_code,a.rule_priority,a.lead_repo";
		return $this->opasa->query($sql);		
	}
	
	//function create new order rule
	//return insert id
	public function createNewRule()
	{
	     $username    = $_REQUEST['username'];
	     $site_id     = $_REQUEST['site_id'];
	     $value_from  = $_REQUEST['value_from'];
	     $value_to    = $_REQUEST['value_to'];
	     $per_month   = $_REQUEST['per_month'];
	     $level       = $_REQUEST['level'];
	     $lead_repo   = $_REQUEST['lead_repo'];
		 $admin_id    = $this->session->userdata('user_id');
		 
		 $sql = "INSERT INTO `order_assign_rule` 
		         (`rule_id`, `username`, `site_id`, `per_month`, `min_order_amount`, `max_order_amount`, `rule_priority`, `lead_repo`,created_date,updated_date,updated_by)                 VALUES (NULL, '$username', '$site_id', '$per_month', '$value_from', '$value_to', '$level', '$lead_repo',NOW(),NOW(),'$admin_id')";
		 if($this->opasa->query($sql))
		 {
		    return $this->opasa->insert_id();
		 }
		 
	}
	
	//get rule details by rule id\
	public function getRulebyId($rule_id)
	{
	     $sql  = "select a.*,b.site_code,b.site_name,c.name from order_assign_rule a 
		          join sites b on a.site_id = b.site_id join users c on a.username = c.username WHERE `rule_id`=$rule_id";
		 return $this->opasa->query($sql);
	}
	
	//delete order assign rule by rule_id
	public function delete_rule($rule_id)
	{
	     $sql  = "delete from order_assign_rule where rule_id = $rule_id";
		 $this->opasa->query($sql);
	}
	
	//update order rule
	public function updateRule($rule_id)
	{
	     $username    = $_REQUEST['username'];
	     $site_id     = $_REQUEST['site_id'];
	     $value_from  = $_REQUEST['value_from'];
	     $value_to    = $_REQUEST['value_to'];
	     $per_month   = $_REQUEST['per_month'];
	     $level       = $_REQUEST['level'];
	     $lead_repo   = $_REQUEST['lead_repo'];
		 $admin_id    = $this->session->userdata('user_id');
		 
		 $sql         = "update `order_assign_rule` set
		                `username` = '$username', `site_id` = '$site_id', `per_month` = '$per_month',`min_order_amount` = '$value_from', `max_order_amount` = '$value_to',
				        `rule_priority` = '$level', `lead_repo` = '$lead_repo',updated_date = NOW(), updated_by = '$admin_id' 
		                 where rule_id = $rule_id";
		 $this->opasa->query($sql);
		 
	}
	
	//function get email alert mail adrress
	function getSettings($key)
	{
	     $sql  = $this->opasa->query("SELECT `setting_value` FROM `opas_settings` WHERE `setting_name`='$key'");
		 $row  = $sql->row();
		 return $row->setting_value;
	}
}