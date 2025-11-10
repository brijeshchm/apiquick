<?php
/**
 *
 * Mail Template When New Lead Arrives.
 *
 */
?>
<html>
<body>
<table class="m_-3031551356041827469MsoNormalTable" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100.0%">
    <tbody>
        <tr>
            <td style="padding:0in 0in 0in 0in">
                <div align="center">
                    <table class="m_-3031551356041827469MsoNormalTable" border="1" cellspacing="0" cellpadding="0" width="630" style="width:472.5pt;background:white;border:solid #cccccc 1.0pt">
                        <tbody>
                           <tr>
                                <td colspan="3" style="border:none;padding:0in 0in 0in 0in">
                                    <div align="center">
                                        <table class="m_-3031551356041827469MsoNormalTable" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100.0%">
                                            <tbody>
                                                <tr style="height:3.75pt">
                                                    <td style="background:#005DFF;padding:0in 0in 0in 0in;height:3.75pt"><p class="MsoNormal" align="center" style="text-align:center"><span style="font-size:14.0pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#fff;font-weight: 900;">quickdials help of provide leads</span><u></u><u></u></p></td>
                                                   
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr style="height:60.0pt">
                                <td width="55%" style="width:55.0%;border:none;padding:0in 7.5pt 0in 7.5pt;height:60.0pt">
                                    <p class="MsoNormal" style="line-height:0%"><span style="font-size:1.0pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"><a href="quickdials.com" title="quickdials.com" target="_blank"><span style="text-decoration:none"><img border="0" id="m_-3031551356041827469_x0000_i1025" src="https://www.quickdials.com/client/images/quick-large-logo.png" alt="quickdials.com" class="CToWUd"></span></a>
                                        </span></p>
                                </td>
                                 <td width="45%" style="border:none;padding:0in 7.5pt 0in 0in;height:60.0pt">
									<p style="margin:0px 0px 0px 0px"><strong>quickdials Media Pvt Ltd</strong></p>
									<p style="margin:0px 0px 0px 0px">G-21 Sector-3 Noida.</p>
									<p style="margin:0px 0px 0px 0px">Phone :+91-7011310265</p>
									<p style="margin:0px 0px 0px 0px">Email : info@quickdials.com</p>
									<p style="margin:0px 0px 0px 0px;">Website : www.quickdials.com</p>
                                </td>
                                <td style="border:none;padding:0in 0in 0in 0in;height:60.0pt"></td>
                            </tr>
                            <tr style="height:.75pt">
                                <td colspan="3" style="border:none;background:#e5e5e5;padding:0in 7.5pt 0in 7.5pt;height:.75pt"></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="border:none;padding:0in 0in 0in 0in">
                                    <table class="m_-3031551356041827469MsoNormalTable" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100.0%">
                                        <tbody>
                                          
                                            <tr>
                                                <td style="padding:22.5pt 15.0pt 22.5pt 15.0pt">
                                                    <div>
                                                        <p class="MsoNormal"><b><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Hi <?php echo $clientname; ?>,</span></b><u></u><u></u></p>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:0in 15.0pt 0in 15.0pt;border-radius:10px">
                                                    <table class="m_-3031551356041827469MsoNormalTable" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100.0%">
                                                        <tbody>
                                                            <tr>
                                                                <td style="border:solid #cccccc 1.0pt;padding:11.25pt 11.25pt 11.25pt 11.25pt">
                                                                    <table class="m_-3031551356041827469MsoNormalTable" border="0" cellspacing="0" cellpadding="0" width="100%" style="width:100.0%">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 15.0pt 0in">
                                                                                    <p class="MsoNormal"><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">You have received an enquiry from our customer. Here are the details:</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Customer Name:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          <?php echo $lead->name; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Shown Interest In:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          <?php echo $lead->kw_text; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">City:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> <?php echo $lead->city_name; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Email ID:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> <?php echo $lead->email; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
																		 
                                                                            <tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Mobile:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> <?php echo $lead->mobile; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
																			<tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Remark From Customer:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> <?php echo $lead->remark; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
																			<tr><td style="padding:18pt 0in 0in 0in;"></td></tr>
																			<tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal" style="text-decoration:underline"><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Note:</span><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> This is a system generated email. Please do not reply.</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="border:none;border-bottom:dashed #cccccc 1.0pt;padding:0in 0in 5.0pt 0in"></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:11.25pt 0in 11.25pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Contact Details of LseadsEdge:</span></strong><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Contact Person :</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          Nitesh Verma</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Email ID :</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          help@quickdials.com</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Mobile number:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          +91-7011310265</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal">&nbsp;<u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal"><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Contact us on the above details for any confussion or clarification. </span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:22.5pt 15.0pt 22.5pt 15.0pt">
                                                    <div>
                                                        <p class="MsoNormal" style="line-height:18.75pt"><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Regards,<br>
      quickdials Media Pvt. Ltd. </span><u></u><u></u></p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </tbody>
</table>
</body>
</html>