<?php
/**
 *
 * Mail Template When New Client Get Register.
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
                                                    <td style="background:#2874F0;padding:0in 0in 0in 0in;height:3.75pt"></td>
                                                    <td style="background:#FB641B;padding:0in 0in 0in 0in;height:3.75pt"></td>
                                                    <td style="background:#2874F0;padding:0in 0in 0in 0in;height:3.75pt"></td>
                                                    <td style="background:#FB641B;padding:0in 0in 0in 0in;height:3.75pt"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <tr style="height:60.0pt">
                                <td width="55%" style="width:55.0%;border:none;padding:0in 7.5pt 0in 7.5pt;height:60.0pt">
                                    <p class="MsoNormal" style="line-height:0%"><span style="font-size:1.0pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"><a href="quickdials.in" title="quickdials.in" target="_blank"><span style="text-decoration:none"><img border="0" id="m_-3031551356041827469_x0000_i1025" src="<?php echo asset('client/images/small-logo.png');  ?>" alt="quickdials.in" class="CToWUd"></span></a>
                                        </span><u></u><u></u></p>
                                </td>
                                <td width="45%" style="border:none;padding:0in 7.5pt 0in 0in;height:60.0pt">
                                    <p class="MsoNormal" align="right" style="text-align:right;line-height:0%"><span style="font-size:1.0pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"><a href="quickdials.in" title="quickdials.in" target="_blank"><span style="text-decoration:none"><img border="0" width="56%" height="47%" id="m_-3031551356041827469_x0000_i1026" src="<?php echo asset('client/images/logo.png'); ?>" alt="quickdials" class="CToWUd"></span></a>
                                        </span><u></u><u></u></p>
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
                                                <td style="background:#232222;padding:12.5pt 6.0pt 12.5pt 6.0pt">
                                                    <p class="MsoNormal" align="center" style="text-align:center"><span style="font-size:14.0pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:white">quickdials helps 10,000+ institutes to fulfill their service needs every year</span><u></u><u></u></p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:22.5pt 15.0pt 22.5pt 15.0pt">
                                                    <div>
                                                        <p class="MsoNormal"><b><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Hi <?php echo $client->business_name; ?>,</span></b><u></u><u></u></p>
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
                                                                                    <p class="MsoNormal"><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">You have successfully registered with quickdials. Here are the details:</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
																			<tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Business Name:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          <?php echo $client->business_name; ?></span><u></u><u></u></p>
                                                                                </td>
																			</tr>
																			<tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">City Registered In:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          <?php echo $client->city; ?></span><u></u><u></u></p>
                                                                                </td>
																			</tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">User ID:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          <?php echo $usr; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Password:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> <?php echo $pass; ?></span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
																			<tr><td style="padding:18pt 0in 0in 0in;"></td></tr>
																			<tr>
                                                                                <td style="padding:0in 0in 7.5pt 0in">
                                                                                    <p class="MsoNormal" style="text-decoration:underline"><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Note:</span><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> This is a system generated password. Kindly change your password after login.</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="border:none;border-bottom:dashed #cccccc 1.0pt;padding:0in 0in 5.0pt 0in"></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:11.25pt 0in 11.25pt 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Contact Details of quickdials:</span></strong><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Contact Person :</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          Rohit </span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Email ID :</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          care@quickdials.com</span><u></u><u></u></p>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding:0in 0in 0in 0in">
                                                                                    <p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Mobile number:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
          +91-</span><u></u><u></u></p>
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
      quickdials Team </span><u></u><u></u></p>
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