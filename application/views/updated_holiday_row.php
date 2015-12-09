       <td width="5%" style="background-color:#00CC33;">#</td>
        <td width="30%" style="background-color:#00CC33;"><?php echo $holiday->holiday_date;?></td>
        <td width="50%" style="background-color:#00CC33;"><?php echo $holiday->subject;?></td>
         <td style="background-color:#00CC33;"><a href="#" class="holiday_delete_<?php echo $holiday->id;?>"><img src="<?php echo base_url()?>images/gnome_edit_delete.png" width="24" height="24" /></a>&nbsp;&nbsp;<a href="#" class="editbtn_holiday_<?php echo $holiday->id;?>"><img src="<?php echo base_url()?>images/list_edit.png" width="20" height="20" /></a></td>
    
	   <script type="text/javascript"> 
  ///delete holiday
 $(document).ready(function() {
	$('a.holiday_delete_<?php echo $holiday->id;?>').click(function(e) {
	var r = confirm('Really want to delete this holiday ?');
	if(r==false)
	{ 
	 return false;
	}
		e.preventDefault();
		var parent = $(this).parent().parent();
		$.ajax({
			type: 'get',
			url: '<?php echo base_url()?>index.php/attendance/delete_holiday',
			data: 'ajax=1&delete=' + parent.attr('id').replace('holiday_',''),
			beforeSend: function() {
			     parent.css('background-color', '#fb6c6c'); //change background color
				//parent.animate({'backgroundColor':'#fb6c6c'},300);
			},
			success: function() {
				   //parent.slideUp(300,function() {
				  // parent.remove();
				  parent.find('td').fadeOut(1000,function(){ 
                  parent.remove();
				});
			}//success end
		});
		
	});
});
 //end delete holiday
  //update form show
  $(document).ready(function() {
	$('a.editbtn_holiday_<?php echo $holiday->id;?>').click(function(e) {
		e.preventDefault();
		var parent = $(this).parent().parent();
		var rul_id = parent.attr('id').replace('holiday_','');
		var form_id = "holiday_form_"+rul_id;
		$.ajax({
			type: 'post',
			url: '<?php echo base_url()?>index.php/attendance/get_edit_form',
			data: 'ajax=1&holiday_id=' + parent.attr('id').replace('holiday_',''),
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
 </script>