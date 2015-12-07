<head>
    <link rel="stylesheet" href="<?php echo base_url()?>js/datepicker/jquery-ui.css">
    <script src="<?php echo base_url()?>js/datepicker/jquery-1.10.2.js"></script>
     <script src="<?php echo base_url()?>js/datepicker/jquery-ui.js"></script>
  <script>
  $(function() {
    //$("#datepicker" ).datepicker();
	$("#datepicker").datepicker({dateFormat: 'yy-mm-dd'});
  });
  </script>
  <script type="text/javascript">
$(function() {
$("#addholiday").click(function() {
    var holiday_date = $("#datepicker").val();
	var subject = $("#subject").val();
	var repeat = 0;
	if($('#repeat').prop("checked") == true){repeat = 1;}
    var dataString = 'holiday_date='+ holiday_date+'&subject='+subject+'&repeat='+repeat;
	if(holiday_date=='')
	{
	 alert("Please Select Date");
	}
	else if(subject=='')
	{
	 alert("Subject is empty");
	}
	else
	{
	    $.ajax({
		type: "POST",
        url: "<?php echo base_url()?>index.php/attendance/mark_holiday",
        data: dataString,
        cache: false,
        success: function(html){
		 document.getElementById("frmholiday").reset();
		  $("#holidaytable tbody").prepend(html);
		    setTimeout(function(){
            $('#holidaytable tr td').css('background-color', '#F4F4F4');}, 2000);
                       }
           });
     }
return false;
	});});
	
	 ///delete holiday
 $(document).ready(function() {
	$('a.holiday_delete').click(function(e) {
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
	$('a.editbtn_holiday').click(function(e) {
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
 //end update form
	</script>
   </head>
       <div class="main-header selw">
      <div class="left-widthr"><b>Holiday List</b></div>
      <br clear="all">
   
	<div class="holidayform"> <form id="frmholiday"><label>Date :</label>
	
        <input name="datepicker" type="text" id="datepicker"> 
		<label>Subject :</label>
        <input name="subject" id="subject" type="text" class="holiday_subj"> <input id="repeat" type="checkbox" value="" class="letbt"> Repeat in next year 
		 <div class="btnleft_rule"><input class="saveForm"  type="submit" value="Add" id="addholiday"></div>
		 </form>
		 </div>
    
    <table width="70%" border="0" class="holidaytable"  id="holidaytable">
	 <tbody>
	 <?php 
	 $i=1;
	 foreach($holidays->result() as $holiday) {?>
        <tr id="holiday_<?php echo $holiday->id;?>">
        <td width="5%">#<?php echo $i;?></td>
        <td width="30%"><?php echo $holiday->holiday_date;?></td>
        <td width="50%"><?php echo $holiday->subject;?></td>
       
        <td width="15%"><a href="#" class="holiday_delete"><img src="<?php echo base_url()?>images/gnome_edit_delete.png" width="24" height="24" /></a>&nbsp;&nbsp;<a href="#" class="editbtn_holiday"><img src="<?php echo base_url()?>images/list_edit.png" width="20" height="20" /></a></td>
      </tr>
	  <tr id="holiday_form_<?php echo $holiday->id;?>" style="display:none;"></tr>
      <?php 
	  $i++;
	  }?>
	  </tbody>
    </table>
  </div>
  <br clear="all" />

