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