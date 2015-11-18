        <?php $rule_id = $rule->rule_id;?>
		<td><select id="user_edit_<?php echo $rule_id;?>" >
		<?php foreach($employee->result() as $user){?>
		<option value="<?php echo $user->username;?>" <?php if($user->username == $rule->username){echo "selected='selected'";}?>><?php echo $user->name;?></option>
		<?php }?>
		<td><select id="site_edit_<?php echo $rule_id;?>">
		<?php foreach($sites->result() as $site){?>
		<option value="<?php echo $site->site_id;?>" <?php if($site->site_id == $rule->site_id){echo "selected='selected'";}?>><?php echo $site->site_code;?></option>
		<?php }?>
		</select>
		</td>
        <td><input type="text" id="value_from_edit_<?php echo $rule_id;?>"  value="<?php echo $rule->min_order_amount;?>" /></td>
        <td><input type="text" id="value_to_edit_<?php echo $rule_id;?>"  value="<?php echo $rule->max_order_amount;?>" /></td>
        <td><input type="text" id="per_month_edit_<?php echo $rule_id;?>"  value="<?php echo $rule->per_month;?>" /></td>
        <td>
		 <select id="levels_edit_<?php echo $rule_id;?>" style="width:45px;">
		<?php for($i=1;$i<=5;$i++) {?>
   <option value="<?php echo $i;?>" <?php if($i == $rule->rule_priority){echo "selected='selected'";}?>><?php echo $i;?></option>
        <?php }?>
 </select>
		</td>
		<td><select id="lead_repo_edit_<?php echo $rule_id;?>" style="width:45px;">
		   <option value="1" <?php if($rule->lead_repo == 1){echo "selected='selected'";}?>>Yes</option>
		    <option value="0" <?php if($rule->lead_repo == 0){echo "selected='selected'";}?>>No</option>
        </select>
 </td>
        <td><a href="#" class="save_edit"><img src="<?php echo base_url()?>images/save.png"  /></a>&nbsp;&nbsp;<a href="#" class="edit_cancel"><img src="<?php echo base_url()?>images/cancel.png"/></a></td>
       <?php // $this->load->view('order_assign_js');?>
<script type="text/javascript" language="javascript">
 //edit camcel button action 
 $(document).ready(function() {
	$('a.edit_cancel').click(function(e) {
		e.preventDefault();
		var parent = $(this).parent().parent();
		var rul_id = parent.attr('id').replace('form_','');
		var rule_tr = "rule_"+rul_id;
		parent.hide();	
		$("#"+rule_tr).show();
	});
});
 //edit cancel button
 
 //edit rule
 $(document).ready(function() {
	$('a.save_edit').click(function(e) {
		e.preventDefault();
		var parent = $(this).parent().parent();
		var rul_id = parent.attr('id').replace('form_','');
		var rule_tr = "rule_"+rul_id;
		
		var username = $("#user_edit_"+rul_id).val();
	    var site_id = $("#site_edit_"+rul_id).val();
	    var value_from = $("#value_from_edit_"+rul_id).val();
	    var value_to = $("#value_to_edit_"+rul_id).val();
	    var per_month = $("#per_month_edit_"+rul_id).val();
	    var level   = $("#levels_edit_"+rul_id).val();
	    var lead_repo = $("#lead_repo_edit_"+rul_id).val();
        var dataString = 'rule_id='+rul_id+'&username='+ username+'&site_id='+site_id+'&value_from='+value_from+'&value_to='+value_to+'&per_month='+per_month+'&level='+level+'&lead_repo='+lead_repo;
	     	$.ajax({
			type: 'post',
			url: '<?php echo base_url()?>index.php/order_rule/edit_rule',
			data: dataString,
            cache: false,
			success: function(data) {
				   parent.hide();
				   $("#"+rule_tr).html(data);
				   $("#"+rule_tr).show();
				   setTimeout(function(){
                   $("#"+rule_tr+" td").css('background-color', '#F4F4F4');}, 2000);
			}//success end
		});
		
	});
}); 
 //end edit rule
 </script>	   
		  