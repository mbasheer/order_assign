<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Attendance</title>
<script src="<?php echo base_url()?>js/jquery-1.11.1.min.js"></script>
<script type="text/javascript">$(document).ready(function(){$(".tabs-menu a").click(function(b){b.preventDefault();$(this).parent().addClass("current");$(this).parent().siblings().removeClass("current");var a=$(this).attr("href");$(".tab-content").not(a).css("display","none");$(a).fadeIn()})});</script>
  <script>
    function moveSelected(from, to) {
	    //alert($('#'+from+' option:selected').val());
		if(from == 'from')
		{
		   $('#'+from+' :selected').each(function(i, selected){ 
            var selected_user = $(selected).val(); 
			var selected_name = $(selected).text();
			 //check this user is unique user for any site
			 $.ajax({
		     type: "POST",
             url: "<?php echo base_url()?>index.php/attendance/check_unique_user",
             data: 'selected_user='+selected_user+'&name='+selected_name,
             cache: false,
             success: function(html){
			      if(html !=0)
				  {
		          $("#absent_info").html(html);
		           $("#absent_info").show();
		           $("#absent_info").delay(5000).fadeOut("slow");
				    //$('#'+from+' option[value="'+selected_user+'"]').prop("selected", false);
                   }
				   else
				   {
				       $('#'+from+' option:selected').remove().appendTo('#'+to);
				   }
				   
				                }	   
                 });
			 //end ajax
			 //$('#'+from+' option[value="shyni"]').prop("selected", false);
			 
            });
		 }
		 else
		 {
		  $('#'+from+' option:selected').remove().appendTo('#'+to);
		}
                
    }
	function submtfrm()
	{
	   $('#from option').prop('selected', true);
	   $('#to option').prop('selected', true);
	}
    </script>
	<!--insert rule ajax-->
<script type="text/javascript">
$(function() {
$(".subf").click(function() {
    var username = $("#employee").val();
	var site_id = $("#sites").val();
	var value_from = $("#value_from").val();
	var value_to = $("#value_to").val();
	var per_month = $("#per_month").val();
	var level   = $("#levels").val();
	var lead_repo = 0;
	var temp_rule = 0;
	if($('#lead_repo').prop("checked") == true){lead_repo = 1;}
	if($('#temp_rule').prop("checked") == true){temp_rule = 1;}
    var dataString = 'username='+ username+'&site_id='+site_id+'&value_from='+value_from+'&value_to='+value_to+'&per_month='+per_month+'&level='+level+'&lead_repo='+lead_repo+'&temp_rule='+temp_rule;
	if(username=='')
	{
	 alert("Please Select Employee");
	}
	else if(site_id=='')
	{
	 alert("Please Select Site");
	}
	else if(level=='')
	{
	 alert("Please Select Level");
	}
	else
	{
	$("#flash").show();
	$("#flash").fadeIn(400).html('<img src="<?php echo base_url();?>images/ajax-loader.gif" align="absmiddle">&nbsp;<span class="loading">Loading Comment...</span>');
       $.ajax({
		type: "POST",
        url: "<?php echo base_url()?>index.php/order_rule/add_new",
        data: dataString,
        cache: false,
        success: function(html){
		 document.getElementById("myForm").reset();
		  $("#update tbody").prepend(html);
		    setTimeout(function(){
            $('#update tr td').css('background-color', '#F4F4F4');}, 2000);
        //$("ol#update li:first").slideDown("slow");
           //document.getElementById('content').value='';
          $("#flash").hide();
		 // $("#inform").show();
		  //$("#inform").delay(3000).fadeOut("slow");
                       }
           });
     }
return false;
	});
	
	
	//run rule now button action
	$("#run_rule").click(function() {
	   $.ajax({
        url: "<?php echo base_url()?>index.php/cron/order_assign",
        cache: false,
		beforeSend: function() {
		 var runningmsg ='Please Wait .....';
		 $('#running').html(runningmsg);
	      $('#running').show();
		   $('#run_rule').hide();
			},
        success: function(){
		                    $('#running').hide(); 
							$('#run_rule').show();
                       }
           });
	  
	})
	
 
});

</script>	
	 <?php  $this->load->view('order_assign_js');?>

<!-- jQuery -->

<!-- Demo stuff -->
<link rel="stylesheet" href="<?php echo base_url()?>css/jq.css">

<link href="<?php echo base_url()?>css/home_style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div class="mainwrap">
<div class="welcome">Welcome <?php echo $this->session->userdata('admin_name');?> <span><a href="<?php echo base_url();?>index.php/attendance/logout">Logout</a></span></div>
<div align="center"><img src="<?php echo base_url()?>images/logo.png" width="151" height="129" /></div>
<div id="tabs-container">
<ul class="tabs-menu">
  <li class="current"><a href="#tab-1">Attendance</a></li>
  <?php if($this->session->userdata('user_type') == 1){?>
  <li><a href="#tab-2">Assign Rules</a></li>
  <?php }?>
</ul>
<div class="tab">
  <div id="tab-1" class="tab-content">
    <div class="main-header">
      <div class="left-widthr"><b>Attendance</b>
        <div>
          <label class="desc" id="title1" for="Field1">Date: <strong><?php echo date('d-m-Y');?></strong></label>
        </div>
      </div>
      <br clear="all" />
    </div>
    <div class="subsec">
      <form name="frm" id="frm" action="" method="post">
        <div class="append">
          
          <h2 class="fty">On Leave </h2>
          <h2 class="fty2">On Duty </h2>
          <select multiple size="10" id="from" name="present[]">
            <?php foreach($present_users->result() as $present) {
			  if($present->username == 'blank'){continue;}
			?>
		<option value="<?php echo $present->username?>"><?php echo $present->name?></option>
        <?php }?>
          </select>
          <div class="controls"> <?php if($restrict!=1){?><a href="javascript:moveSelected('from', 'to')">&gt;</a> <a href="javascript:moveSelected('to', 'from')">&lt;</a> <?php }?></div>
          
		  <select multiple id="to" size="10" name="absent[]">
		   <?php foreach($absent_users->result() as $absent) {?>
		<option value="<?php echo $absent->username?>"><?php echo $absent->name?></option>
        <?php }?>
          </select> <label id="absent_info"></label>
        </div>
      
    </div>
    <div class="tablen">
	 <div style="color:red;"><?php echo $msg;?></div>
      <div class="btnleft">
        <?php if($restrict!=1){?><input id="saveForm"  name="save" type="submit" value="Save" onClick="submtfrm();"></form>
		<?php } else echo '<span style="color:red">Attendance marking allowed only till 9 AM, please contact administrator</span>';?>
      </div>
    </div>
  </div>
  <div id="tab-2" class="tab-content">
    <div class="main-header selw">
      <div class="left-widthr"><b>Order Assign Rule</b></div>
      <br clear="all">
      <div class="leftform">
	  <form name="rulfrm" id="myForm" action="#" method="post">
        <label>Employee: </label>
         <select id="employee" required>
		  <option value="">-Select-</option>
           <?php foreach($employee->result() as $user){?>
		   <option value="<?php echo $user->username;?>"><?php echo $user->name;?></option>
		   <?php }?>   
         </select>
      </div>
      <div class="leftform">
        <label>Site: </label>
         <select id="sites" required>
		   <option value="">-Select-</option>
           <?php foreach($sites->result() as $site){?>
		   <option value="<?php echo $site->site_id;?>"><?php echo $site->site_name;?></option>
		   <?php }?> 
        </select>
      </div>
      <div class="leftform">
        <label>Order value From: </label>
        <input id="value_from" type="number">
      </div>
      <div class="leftform">
        <label>To: </label>
        <input id="value_to" type="number">
      </div>
      <div class="leftform">
        <label>Order per month: </label>
        <input id="per_month" type="number">
      </div>
      <div class="leftform">
        <label>Levels: </label>
        <select id="levels" required>
		<option value="">-Select-</option>
   <option value="1">1</option>
   <option value="2">2</option>
   <option value="3">3</option>
   <option value="4">4</option>
   <option value="5">5</option>
 </select>
   
    </div>
     <div class="forbtn">
    <input name="lead_repo" id="lead_repo" type="checkbox" value="1" class="letbt">
    <label style="text-align:left;">Main representative</label>
    </div>  <div class="forbtn" style="width:390px;">
    <input name="temp_rule" id="temp_rule" type="checkbox" value="1" class="letbt">
    <label style="text-align:left;">This rule will be removed after 24 hrs</label>
    </div>
	<div style="clear:both;"></div>
     <div class="btnleft_rule">
     
       
          <input id="saveForm"  type="submit" value="Add Rule" class="subf">
      
      </div>
    </form>
	<div id="inform" align="left" style="color:#993300; display:none;">New Rule Created Successfully</div>
	<div id="flash" align="left"  ></div>
	<div class="runrule" id="runrule"><a href="#" id="run_rule" title="Run Rule" alt="Run Rule">Run Rule Now</a></div><div class="runrule" id="running"></div>
    <table width="100%" border="0" class="imagetable" id="update" >
      <thead>
	  <tr>
       <th width="21%" scope="col">Employee</th>
        <th width="15%" scope="col">Site Code</th>
        <th width="12%" scope="col">Value From($)</th>
        <th width="12%" scope="col">Value To($)</th>
        <th width="9%" scope="col">Per Month</th>
		 <th width="10%" scope="col">Level</th>
        <th width="11%" scope="col">Main Repo</th>
        <th width="10%" scope="col">Action</th>
      </tr>
	  </thead>
	  <tbody>
	  <?php foreach($rules->result() as $rule) {?>
      <tr id="rule_<?php echo $rule->rule_id;?>" <?php if($rule->is_temp == 1){echo "style='color:#3399CC;font-weight:bold;'";}?> >
        <td><?php echo $rule->name;if($rule->is_temp == 1){echo " (Only for 24 hrs)";}?></td>
        <td><?php echo $rule->site_code;?></td>
        <td><?php echo $rule->min_order_amount;?></td>
        <td><?php echo $rule->max_order_amount;?></td>
        <td><?php echo $rule->per_month;?></td>
        <td><?php echo $rule->rule_priority;?></td>
		<td><?php echo $a = $rule->lead_repo?'Yes':'No';?></td>
        <td><a href="#" class="delete"><img src="<?php echo base_url()?>images/gnome_edit_delete.png" width="24" height="24" /></a>&nbsp;&nbsp;<a href="#" class="editbtn"><img src="<?php echo base_url()?>images/list_edit.png" width="20" height="20" /></a></td>
      </tr>
	  <tr id="form_<?php echo $rule->rule_id;?>" style="display:none;"></tr>
      <?php }//end rules foreach?>
	  </tbody>
    </table>
	
  </div>
  <br clear="all" />
</div>
</body>
</html>
