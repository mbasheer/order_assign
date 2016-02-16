        <td style="background-color:#00CC33;"><?php echo $rule->name;?></td>
        <td style="background-color:#00CC33;"><?php echo $rule->site_code;?></td>
         <?php if(@$rule_type!=3) {?>
        <td style="background-color:#00CC33;"><?php echo $rule->min_order_amount;?></td>
        <td style="background-color:#00CC33;"><?php echo $rule->max_order_amount;?></td>
        <?php }?>
        <td style="background-color:#00CC33;"><?php echo $rule->per_month;?></td>
		<td style="background-color:#00CC33;"><?php echo $rule->month_cnt;?></td>
        <td style="background-color:#00CC33;"><?php echo $rule->rule_priority;?></td>
		<td style="background-color:#00CC33;"><?php echo $a = $rule->lead_repo?'Yes':'No';?></td>
        <td style="background-color:#00CC33;"><a href="#" class="delete_<?php echo $rule->rule_id;?>"><img src="<?php echo base_url()?>images/gnome_edit_delete.png" width="24" height="24" /></a>&nbsp;&nbsp;<a href="#" class="editbtn_<?php echo $rule->rule_id;?>"><img src="<?php echo base_url()?>images/list_edit.png" width="20" height="20" /></a></td>
    
	   <script type="text/javascript"> 
// ordr assign tab javacript view
 ///delete rule
 $(document).ready(function() {
	$('a.delete_<?php echo $rule->rule_id;?>').click(function(e) {
	var r = confirm('Really want to delete this rule ?');
	if(r==false)
	{ 
	 return false;
	}
		e.preventDefault();
		var parent = $(this).parent().parent();
		$.ajax({
			type: 'get',
			url: '<?php echo base_url()?>index.php/order_rule/delete',
			data: 'ajax=1&delete=' + parent.attr('id').replace('rule_',''),
			beforeSend: function() {
			     parent.css('background-color', '#fb6c6c'); //change background color
				//parent.animate({'backgroundColor':'#fb6c6c'},300);
			},
			success: function(msg) {
				 if(msg == 1)
				 { 
				   parent.find('td').fadeOut(1000,function(){ parent.remove(); });
				 } 
				 else
				 {
				    alert("Only one assigning person for this product , please add another person to remove this rule");
				 }
			}//success end
		});
		
	});
});
 //end delete rule
 //update form show
  $(document).ready(function() {
	$('a.editbtn_<?php echo $rule->rule_id;?>').click(function(e) {
		e.preventDefault();
		var parent = $(this).parent().parent();
		var rul_id = parent.attr('id').replace('rule_','');
		var form_id = "form_"+rul_id;
		$.ajax({
			type: 'post',
			url: '<?php echo base_url()?>index.php/order_rule/get_edit_form',
			data: 'ajax=1&rule_id=' + parent.attr('id').replace('rule_',''),
			beforeSend: function() {
			     //parent.css('background-color', '#fb6c6c'); //change background color
				//parent.animate({'backgroundColor':'#fb6c6c'},300);
			},
			success: function(data) {
				   parent.hide();
				   $("#"+form_id).html(data);
				   $("#"+form_id).show();
			}//success end
		});
		
	});
});
 //end update form
 

 
 </script>