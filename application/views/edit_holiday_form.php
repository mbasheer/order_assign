		<?php $id = $holiday->id;?>
		<td width="5%">#</td>
        <td width="30%"><input name="datepicker" type="text" class="datepicker" value="<?php echo $holiday->holiday_date;?>" id="datepicker_<?php echo $id;?>"></td>
        <td width="50%"><input name="subject" id="subject_<?php echo $id;?>" type="text" class="holiday_subj" value="<?php echo $holiday->subject;?>"></td>
        <td width="15%"><a href="#" class="save_edit"><img src="<?php echo base_url()?>images/save.png"  /></a>&nbsp;&nbsp;<a href="#" class="edit_cancel"><img src="<?php echo base_url()?>images/cancel.png"/></a></td>

  <script>
  $(function() {
    //$("#datepicker" ).datepicker();
	$(".datepicker").datepicker({dateFormat: 'yy-mm-dd'});
  });
  </script>
  	
<script type="text/javascript" language="javascript">
 //edit camcel button action 
 $(document).ready(function() {
	$('a.edit_cancel').click(function(e) {
		e.preventDefault();
		var parent = $(this).parent().parent();
		var holiday_id = parent.attr('id').replace('holiday_form_','');
		var rule_tr = "holiday_"+holiday_id;
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
		var holiday_id = parent.attr('id').replace('holiday_form_','');
		var holiday_tr = "holiday_"+holiday_id;
		
		 var holiday_date = $("#datepicker_"+holiday_id).val();
	     var subject = $("#subject_"+holiday_id).val();
		
        var dataString = 'holiday_id='+holiday_id+'&holiday_date='+ holiday_date+'&subject='+subject;
	     	$.ajax({
			type: 'post',
			url: '<?php echo base_url()?>index.php/attendance/edit_holiday',
			data: dataString,
            cache: false,
			success: function(data) {
				   parent.hide();
				   $("#"+holiday_tr).html(data);
				   $("#"+holiday_tr).show();
				   setTimeout(function(){
                   $("#"+holiday_tr+" td").css('background-color', '#F4F4F4');}, 2000);
			}//success end
		});
		
	});
}); 
 //end edit rule
 </script>	   
		  