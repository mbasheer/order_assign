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
	
	public function selectAllSites()
	{
	   $sql = "SELECT * FROM `sites`";
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
		
		$sql  = "SELECT `order_id`,`date_added`, total, createduser_id, email FROM `order` 
		         WHERE (`customer_assign` = 0 or customer_assign IS NULL) AND (date_added > DATE_SUB(NOW(), INTERVAL 1 WEEK) or date_modified > DATE_SUB(NOW(), INTERVAL 1 WEEK)) 
				 AND order_status_id not in(".EXCEPT_STATUS.") 
				 order by date_added";	
		if($site_code == 'BKC')
		{
		   //include processing order too
		  $sql  = "SELECT `order_id`,`date_added`, total, createduser_id, email FROM `order` 
		         WHERE (`customer_assign` = 0 or customer_assign IS NULL) AND (date_added > DATE_SUB(NOW(), INTERVAL 1 WEEK) or date_modified > DATE_SUB(NOW(), INTERVAL 1 WEEK)) 
				 AND order_status_id not in(45,47) 
				 order by date_added"; 
		}		 
		/*$sql  = "SELECT `order_id`,`date_added`, total FROM `order` 
		         WHERE (`customer_assign` = 0 or customer_assign IS NULL)
				 order by date_added desc limit 2";	 */
		return $this->site_db->query($sql); 
	}
	
	//return assigned username
	//input - site id , order id, email, created user_id (o- craeted by customer itself), and price
	public function orderAssignUser($site_id,$order_id,$cust_email, $order_price,$site_code,$createduser)
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
		/*if($createduser != 0)
		{
		   //if any created user id
		   //gt that username from user_id
		   $username = $this->getUsernamefromId($createduser);
		   if($username){return $username;}
		}*/
		
		
		//if this order is a copy/reorder of another order , assigned to same user
		//return previous order asisgne drepo name 
		//else return 0
		$reorder_repo     = $this->checkReOrder($order_id, $cust_email);
		if($reorder_repo)
		{
		   //check check this user still in that site's rule list
		   //otherwise this order act as a normal order 
		   $present_status_sql = $this->opasa->query("select rule_id from order_assign_rule where username = '$reorder_repo' and site_id = $site_id");
           //if username 
		   if($present_status_sql->num_rows() >  0)	
		   {												  
			   return $reorder_repo; //parent order assigned username
		   }	 
		}
		
		//first we have to find which user is best for this order from order assign rule
		//then check that user is not exceeds the threshold
		//threshold - max order per month where MONTH(CURDATE())=MONTH(assign_date)
		//first round chek , 
		//get all user satisfy the assign rule with this order id 
										  
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
		     $best_lead_repo      = "select a.username from order_assign_rule a where a.lead_repo = 1 and a.site_id = $site_id";
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
			 $query_order_update = "update `order` set customer_assign = $user_id, date_modified = now()  where order_id = '$order_id'";
			 
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
	
	//check this order email have previous order
	//if yes return just previous order assigned username
	//else return 0
	function checkReOrder($order_id, $email)
	{
	   //select prevoius order from this email id , assigned <> 0 
	   $query_reorder   = "SELECT a.username  FROM `user` a join `order` b on a.user_id = b.`customer_assign` 
	                       WHERE b.`customer_assign` <> 0 and b.`email` = '$email' and b.`order_id` <> '$order_id' and b.order_status_id not in(".EXCEPT_STATUS.") order by b.`date_modified` desc limit 1";
	   $sql_reorder     = $this->site_db->query($query_reorder);
	   if($sql_reorder->num_rows()<1)
	   {
          //if no reorder or no prevoius assigned order
		  return 0;	   
	   }
	   
	   $row_reorder     = $sql_reorder->row();
	   return $row_reorder->username;
	  
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
	    $sql = "SELECT * FROM `users` where username <> 'blank' ORDER BY `name`";
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
	    $sql = "select a.*,b.site_code,b.site_name,c.name,count(d.id) as month_cnt 
		        from order_assign_rule a join sites b on a.site_id = b.site_id 
			    join users c on a.username = c.username 
				left join assign_orders d on a.username = d.username and a.site_id = d.site_id 
				and MONTH(d.assign_date) = MONTH(CURDATE()) and YEAR(d.assign_date) = YEAR(CURDATE())
				group by b.site_code,b.site_name,c.name 
				order by b.site_code,a.rule_priority,a.lead_repo";
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
	     $sql  = "select a.*,b.site_code,b.site_name,c.name,count(d.id) as month_cnt 
		          from order_assign_rule a 
		          join sites b on a.site_id = b.site_id join users c on a.username = c.username
				  left join assign_orders d on a.username = d.username and a.site_id = d.site_id and MONTH(d.assign_date) = MONTH(CURDATE()) and YEAR(d.assign_date) = YEAR(CURDATE()) 
				  WHERE `rule_id`=$rule_id ";
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
	
	
	//return diff order with changed username
	public function getAllReAssignOrder($site_code,$site_id)
	{
	    $CI = &get_instance();
		$this->site_db = $CI->load->database($site_code, TRUE);
		//get order_ids
		//avoid old orders
		//select order only from one week time frame
		$query_assigned_site = "select a.order_id,b.username from `order` a join user b on a.customer_assign = b.user_id 
		                        where (a.date_added > DATE_SUB(NOW(), INTERVAL 5 DAY) or a.date_modified > DATE_SUB(NOW(), INTERVAL 5 Day)) and a.order_status_id not in(".EXCEPT_STATUS.")";
		$sql_assigned_site   = $this->site_db->query($query_assigned_site); 
		//add this result to array for comparison
		$site_order_array = array(); 
		foreach($sql_assigned_site->result() as $row_assigned_site)
		{
		  $user_name = $row_assigned_site->username;
		  $order_id  = $row_assigned_site->order_id;
		  $site_order_array[$order_id] = $user_name;
		}
		//get assigned order from opas table
		$assigned_order_query = "select username, order_id from assign_orders where assign_date > DATE_SUB(NOW(), INTERVAL 7 DAY) and site_id = '$site_id'"	;
		$assigned_order_sql   = $this->opasa->query($assigned_order_query); 
		//add this result to array for comparison
		$opas_order_array = array(); 
		foreach($assigned_order_sql->result() as $assigned_order_row)
		{
		  $user_name = $assigned_order_row->username;
		  $order_id  = $assigned_order_row->order_id;
		  $opas_order_array[$order_id] = $user_name;
		}
		$diff_orders = array_diff_assoc($site_order_array, $opas_order_array)	;
		return $diff_orders;  
	}
	
	public function changeReorderUser($order_id, $user_name, $site_id)
	{
	    $sql = "update assign_orders set username = '$user_name' where site_id = '$site_id' and order_id = '$order_id'";
		$this->opasa->query($sql);
	}
	//delete temp rule
	public function deleteTempRule()
	{
	    $sql = "delete from order_assign_rule where date(created_date) < CURDATE() and is_temp = 1";
		$this->opasa->query($sql);
	}
	
	//getallassigned order from website
	public function getAllordersBySite($site_id,$site_code)
	{
	    $CI = &get_instance();
		$this->site_db = $CI->load->database($site_code, TRUE);
		
		$sql_assignedOrders = $this->site_db->query("SELECT a.order_id, a.date_added, b.username FROM `order` a JOIN user b ON a.customer_assign = b.user_id WHERE a.customer_assign <>0 AND (a.date_added > DATE_SUB( NOW( ) , INTERVAL 4 DAY )) and a.order_status_id not in(".EXCEPT_STATUS.")");
		return $sql_assignedOrders;
	}
	
	//sync wesite orders to opas table
	//if order is already in table no action
	//else insert into assign order table<br />
    public function syncOrder($site_id,$order_id,$username,$dateadded)
	{
	    //check this order is present in opas table
		//else insert
		$check_sql = $this->opasa->query("select * from assign_orders where order_id = '$order_id' and site_id ='$site_id'");
		if($check_sql->num_rows() < 1)
		{
		  //insert
		  //insert order assign details to master database  
		   $sql    = "INSERT INTO `assign_orders` (`id`, `username`, `site_id`, `order_id`, `assign_date`) 
				           VALUES (NULL, '$username', '$site_id', '$order_id', '$dateadded')";
		   $this->opasa->query($sql);	
		}
	}
	
	//get latest test/trash orders 
	public function getAllTestOrders($site_code,$site_id)
	{
	    $CI = &get_instance();
		$this->site_db = $CI->load->database($site_code, TRUE);
		//get order_ids
		//avoid old orders
		//select order only from 3 day time frame
		$query_test_orders   = "select a.order_id from `order` a  
		                        where (a.date_added > DATE_SUB(NOW(), INTERVAL 3 DAY) or a.date_modified > DATE_SUB(NOW(), INTERVAL 3 Day)) 
								and a.order_status_id in(45,47)";
		return $this->site_db->query($query_test_orders); 
	}
	
	public function deleteOrderAssign($order_id,$site_id)
	{
	    $sql = "delete from assign_orders where order_id = '$order_id' and site_id = '$site_id'";
		$this->opasa->query($sql);
	}
	
	//get count of rule of site using ruleid
	public function countRulesBySite($rule_id)
	{
	   $query = "SELECT count(*) as cnt FROM `order_assign_rule` WHERE `site_id` = (select `site_id` from order_assign_rule where `rule_id` ='$rule_id')";
	   $sql   = $this->opasa->query($query);
	   $row   = $sql->row();
	   return $row->cnt;
	}
	
}