<html>
<head>	
 <meta content="text/html; charset=iso-8859-1" http-equiv="Content-Type" />
</head>
<body marginheight="0" topmargin="0" marginwidth="0" leftmargin="0" bgcolor="#f4f4f4">
   <table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" bgcolor="#f4f4f4">
     <tr>
        <td>
          <table width="730" align="center" border="0" cellspacing="0" cellpadding="0">
            
            <tr>
               <td align="left" valign="top" bgcolor="#CCCCCC" style="padding:1px;">
               
               
               <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#eee;">
                    <tr>
                      <td width="55%" align="left" valign="top" style="padding:10px 0 20px 15px;"><a href="#"  target="_blank"> <img src="<?php echo base_url(); ?>images/logo.png" alt="logo" border="0" align="left"></a></td>
                      <td width="45%" align="left" valign="top" style="padding:10px 15px 0 0;"></td>
                    </tr>
                  </table>
               
               
                  <table width="730" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF">
                     <tr>
                        <td align="left" valign="top" style="padding:20px 20px 0px 20px;">						</td>
					 </tr>
                   
                     <tr>
	                    <td align="left" valign="top" style="line-height:14px; font-size:11px; color:#676767;  font-family:Tahoma; padding-left:20px; padding-right:20px; padding-top:13px;">
						  Dear Admin,	                    </td>
	                 </tr>
                     <tr>
	                    <td align="left" valign="top" style="line-height:14px; font-size:11px; color:#676767;  font-family:Tahoma; padding-left:20px; padding-right:20px; padding-top:13px;">
						  <table><tr><th style="width:100px;text-align:left;">Website</th><th style="width:150px;text-align:left;">Order Id</th><th style="width:150px;text-align:left;">Order Price</th></tr>
						  <?php while($order = array_shift($not_assigned))
		                       {
		                          $order_id    = $order['order_id'];
			                      $website     = $order['site_code'];
			                      $order_price = $order['order_price'];
			                      echo '<tr><td>'.$website.'</td><td>'.$order_id.'</td><td>'.$order_price.'</td></tr>';
		                        }
		                           echo "</table>";?>
						                          </td>
                     </tr>
                   
	                 <tr>
	                    <td align="left" valign="top" style="line-height:17px; font-size:15px; color:#676767;  font-family:Tahoma; padding-left:20px; padding-right:20px; padding-top:13px;">
						  These Orders are remaining unassigned.System tried to assign these orders on <?php echo date('Y-m-d h:i a');?>, but couldn't find any main rep on duty for these sites.<br> Please do the needful. <br>
		   <a href='http://hr.directpromotionals.com/' target='_blank'>Order Assign Rule</a>						</td>
                     </tr>
                     
                     <tr>
                        <td align="left" valign="top" style="line-height:14px; font-size:11px; color:#676767;  font-family:Tahoma; padding-left:20px; padding-bottom:20px; padding-right:20px; padding-top:13px;">
                           <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>

    <td width="49%" align="left" valign="top" style="line-height:14px; font-size:11px; color:#676767;  font-family:Tahoma; ">
								   Regards,<br />
		                          directpromotionals.com  </td>
    <td width="31%" align="left" valign="middle" style="line-height:14px; font-size:11px; color:#676767;  font-family:Tahoma; font-weight:bold; ">&nbsp;</td>
    <td width="5%" align="left" valign="top" style="padding-top:3px;">&nbsp;</td>
    <td width="5%" align="left" valign="top" style="padding-top:3px;">&nbsp;</td>
    <td width="5%" align="left" valign="top" style="padding-top:3px;">&nbsp;</td>
    <td width="5%" align="left" valign="top" style="padding-top:3px;">&nbsp;</td>
  </tr>
  
</table>					   </td>
                        </tr>
						<tr><td></td></tr>
                     </table>
              </td>
                </tr>
               
             </table>
           </td>
        </tr>
    </table>
</body>
</html>
