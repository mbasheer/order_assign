
 <thead>
	  <tr>
       <th width="15%" scope="col">Employee</th>
        <th width="10%" scope="col">Site Code</th>
        <th width="12%" scope="col">Value From($)</th>
        <th width="12%" scope="col">Value To($)</th>
        <th width="9%" scope="col">Per Month</th>
		<th width="12%" scope="col">Assigned orders</th>
		 <th width="10%" scope="col">Level</th>
        <th width="11%" scope="col">Main Repo</th>
        <th width="10%" scope="col">Action</th>
      </tr>
	  </thead>
	  <tbody>
	  <?php foreach($rules->result() as $rule) {?>
      <tr id="rule_<?php echo $rule->rule_id;?>"  >
        <td><?php echo $rule->name;?></td>
        <td><?php echo $rule->site_code;?></td>
        <td><?php echo $rule->min_order_amount;?></td>
        <td><?php echo $rule->max_order_amount;?></td>
        <td><?php echo $rule->per_month;?></td>
		<td><?php echo $rule->month_cnt;?></td>
        <td><?php echo $rule->rule_priority;?></td>
		<td><?php echo $a = $rule->lead_repo?'Yes':'No';?></td>
        <td><a href="#" class="delete"><img src="<?php echo base_url()?>images/gnome_edit_delete.png" width="24" height="24" /></a>&nbsp;&nbsp;<a href="#" class="editbtn"><img src="<?php echo base_url()?>images/list_edit.png" width="20" height="20" /></a></td>
      </tr>
	  <tr id="form_<?php echo $rule->rule_id;?>" style="display:none;"></tr>
      <?php }//end rules foreach?>
	  </tbody>
      <script type="text/javascript"> 
// ordr assign tab javacript view
 ///delete rule
 $(document).ready(function() {
	$('a.delete').click(function(e) {
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
			
				   //parent.slideUp(300,function() {
				  // parent.remove();
				 if(msg == 1)
				 { 
				   parent.find('td').fadeOut(1000,function(){ parent.remove(); });
				 } 
				 else
				 {
				     var informmsg = "Only one assigning person for this product , please add another person to remove this rule";
					 /*$('#inform').html(informmsg);
					  $('#inform').focus();
	                 $('#inform').show();
					 $('#inform').fadeOut(10000,function(){ $('#inform').hide(); });*/
					 alert(informmsg);
				 }
			}//success end
		});
		
	});
});
 //end delete rule
 //update form show
  $(document).ready(function() {
	$('a.editbtn').click(function(e) {
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