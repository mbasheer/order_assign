<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Order_rule extends CI_Controller {
	//constructor function
	//runs this controller loads 
	public function __construct()
    {
         parent::__construct();
          // Your own constructor code
		  //if no session redirect to login page
	      if(!$this->session->has_userdata('user_id'))
	      {
	        redirect(base_url().'index.php/attendance/login');
	      }
		  // load order_model database
		  $this->load->model('order_model','order');
    } 
	
	public function add_new()
	{
		//create new rule and get rule_id
		$rule_id  = $this->order->createNewRule();
		//if successfully insert the rule
		//create html 
		if($rule_id)
		{
		    $rule_result  = $this->order->getRulebyId($rule_id);
			$data['rule']  = $rule_result->row();
			//new rule row view
			$this->load->view('new_rule_row',$data);
		}
	}
	
	//delete rule
	public function delete()
	{
	    if(isset($_GET['delete'])) 
		{
		   $rule_id = $_GET['delete'];
		   //check this rule is unique for that product , if yes dont allow to remove this rule
		   $count_rule = $this->order->countRulesBySite($rule_id);
		   if($count_rule == 1)
		   {
		      //return error message
			  echo 0;
		   }
		   else
		   {
		     $this->order->delete_rule($rule_id);
			 echo 1;
		   }	 
		}
	}
	
	//get edit form by rule_id
	public function get_edit_form()
	{
	    if(isset($_REQUEST['rule_id'])) 
		{
		   $rule_id = $_REQUEST['rule_id'];
		   $data['employee']     = $this->order->getUsersList();
	       $data['sites']        = $this->order->getSiteList();
		   $rule_result          = $this->order->getRulebyId($rule_id);
		   $data['rule']         = $rule_result->row();
		   $this->load->view('edit_rule_form',$data);
		   
		}
	}
	
	//edit rule form
	function edit_rule()
	{
	    //update rule
		$rule_id  = $_REQUEST['rule_id'];
		$this->order->updateRule($rule_id);
		//create revised html with new changes 
		$rule_result  = $this->order->getRulebyId($rule_id);
		$data['rule']  = $rule_result->row();
		//new rule row view
		$this->load->view('updated_rule_row',$data);
		
	}
}	