<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
	<title>quickdials-Invoice_<?php echo date('d-m-Y H:i:s'); ?></title>
 
		<style>
			/* reset */

*
{
	border: 0;
	box-sizing: content-box;
	color: inherit;
	font-family: inherit;
	font-size: inherit;
	font-style: inherit;
	font-weight: inherit;
	line-height: inherit;
	list-style: none;
	margin: 0;
	padding: 0;
	text-decoration: none;
	vertical-align: top;
}

/* content editable */

 

/* heading */

aside h1 { font: bold 55% sans-serif; letter-spacing: 0.1em; }

/* table */

table { font-size: 75%; table-layout: fixed; width: 90%; }
table { border-spacing: 0;border-collapse: collapse; }
th, td { border-width: 1px; padding: 0.5em; position: relative; text-align: left; }
th, td { border-radius: 0.25em; border-style: solid; }
.meta th { background: none; border-color: #BBB; }
th { background: #EEE; border-color: #BBB; }
td {     border: 1px solid #ddd; }

/* page */

html { font: 16px/1 'Open Sans', sans-serif; overflow: auto; padding: 0.5in; }
html { background: #999; cursor: default; }

body { box-sizing: border-box; height: 11in; margin: 0 auto; overflow: hidden; padding: 0.5in; width: 8.5in; }
body { background: #FFF; border-radius: 1px; box-shadow: 0 0 1in -0.25in rgba(0, 0, 0, 0.5); }

/* header */

header { margin: 0 0 3em; }
 
header:after { clear: both; content: ""; display: table; }

header h1 {  border-radius: 0.25em; color: #000; margin: 0 0 1em; padding: 0.5em 0; font: bold 55% sans-serif;letter-spacing: 0.5em;text-align: center;text-transform: uppercase;width: 100%;font-size: 15px;}
header address { float: left; font-size: 75%; font-style: normal; line-height: 1.25; margin: 0 1em 1em 0; }
header address p { margin: 0 0 0.25em; }
header span, header img { display: block; float: left; }
header span { margin: 0px; max-height: 100px; position: relative; }
 

header input { cursor: pointer; -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=0)"; height: 10px; left: 0; opacity: 0; position: absolute; top: 0; width:100%; }

/* article */

article, article address, table.meta{ margin: 0 0 0em; margin-top: 0px; }
article, table.inventory { margin: 0 0 1em;  }
article:after { clear: both; content: ""; display: table; }
article h1 { clip: rect(0 0 0 0); position: absolute; }

article address { float: left; font-size: 125%; font-weight: bold; }

/* table meta & balance */

table.meta, { float: right; width: 30%;margin-right:20%;border:none; }
table.meta, table.balance { float: right; width: 40%; }
table.meta:after, table.balance:after { clear: both; display: table; }

/* table meta */

table.meta th { width: 30%; height: 1%;border:none}
table.meta td { width: 30%; height: 1%;border:none}
table.meta strong { font-weight: 700;}
 


table.receiver { clear: both; width: 90%; }
table.receiver th { font-weight: bold; text-align: center; }

table.receiver td:nth-child(1) { width: 50%; }
table.receiver td:nth-child(2) { width: 50%; }
table.receiver td:nth-child(3) { text-align: right; width: 12%; }
table.receiver td:nth-child(4) { text-align: right; width: 12%; }
table.receiver td:nth-child(5) { text-align: right; width: 12%; }
table.receiver td:nth-child(6) { text-align: right; width: 12%; }
table.receiver td{border: none;}
.receiver p {
        margin: 7px 0 9px;
}
/* table balance */

table.balance th, table.balance td { width: 50%; }
table.balance td { text-align: right; }

/* aside */

aside h1 { border: none; border-width: 0 0 1px; margin: 0 0 1em; }
aside h1 { border-color: #999; border-bottom-style: solid; width: 90%;}

/* javascript */

.add, .cut
{
	border-width: 1px;
	display: block;
	font-size: .8rem;
	padding: 0.25em 0.5em;	
	float: left;
	text-align: center;
	width: 0.6em;
}

.add, .cut
{
	background: #9AF;
	box-shadow: 0 1px 2px rgba(0,0,0,0.2);
	background-image: -moz-linear-gradient(#00ADEE 5%, #0078A5 100%);
	background-image: -webkit-linear-gradient(#00ADEE 5%, #0078A5 100%);
	border-radius: 0.5em;
	border-color: #0076A3;
	color: #FFF;
	cursor: pointer;
	font-weight: bold;
	text-shadow: 0 -1px 2px rgba(0,0,0,0.333);
}

.add { margin: -2.5em 0 0; }

.add:hover { background: #00ADEE; }

.cut { opacity: 0; position: absolute; top: 0; left: -1.5em; }
.cut { -webkit-transition: opacity 100ms ease-in; }

tr:hover .cut { opacity: 1; }

@media print {
	* { -webkit-print-color-adjust: exact; }
	html { background: none; padding: 0; }
	body { box-shadow: none; margin: 0; }
	span:empty { display: none; }
	.add, .cut { display: none; }
}
.b{
	    font-size: 18px;
}
.authrise{
	    margin-top: 100px;
}

.amount{	
	float: left;
    width: 90%;
}
.td-amount{
	height: 27px;
	text-align: right;
    padding-top: 44px;
    padding-right: 100px;
}
hr{
	border-color: #999; border-bottom-style: solid; 
	    font: bold 55% sans-serif;
    letter-spacing: 0.1em;	    
    border-width: 0 0 1px;
    margin: 0 0 1em;
	width: 90%;
}

.receiver .address-left{
    float: left;
    /* font-size: 75%; */
    font-style: normal;
    line-height: 1.25;
    margin: 0 1em 1em 0;
}

.receiver .address-right {
    float: right;
    font-size: 75%;
    font-style: normal;
    line-height: 1.25;
    margin: 0 1em 1em 0;
}
@page { margin: 0; }

.imgarea { width:100%; border: none;}
.imgcell { text-align:center; border: none;}
.imgcell img { max-width:250px; max-height:200px; width:150px; }
</style>
	</head>
	<body>
		<header>
			<table class="imgarea">
			<tr>
				<td class="imgcell" style="border: none !important;"> 
					<img src="{{ $imageSrc }}" alt="Logo">
				</td>
			</tr>
			</table><br><br>
			<table >			 
				<tr style="text-align: center;">
					<td style="border: none !important;">
						<h1 style="font-weight:700;font-size:15px">TAX-Invoice</h1>
					</td>
				</tr>
			</table>			
			<address>
				<b style="font-size: 18px;">Quick Dials Pvt Ltd</b>
				<p> G-13, Sector-3 Noida, U.P, India </p>
				<p>Phone : 120-49999</p>
				<p>Email : info@quickdials.com</p>
				<p>Website : www.quickdials.com</p>
			 
			</address>
			<table class="meta" width="60%" >
				<tr>
					<th><span >GSTIN</span></th>
					<td><strong>09AAECL0574H1ZG</strong></td>
				</tr>
				<tr>
					<th><span >PAN No</span></th>
					<td><strong >AAECL0574H</strong></td>
				</tr>			 
				<tr>
					<th><span >CIN No</span></th>
					<td><strong >MRTTL01615F</strong></td>
				</tr>							 
				<tr>
					<th><span >Serial No</span></th>
					<td><strong >{{$paymentprint->order_number??''}}</strong></td>
				</tr>			 
				<tr>
					<th><span >Date of Invoice</span></th>
					<td><strong ><?php echo date('d-M-Y',strtotime($paymentprint->order_date)); ?></strong></td>
				</tr>			 
			</table> 
		</header>
		<hr>		
		<article>					 
					
			 <b style="margin-left:8px;font-size: 12px;font-weight: bold;">Details of Receiver (Billed) </b>
			   <b style="float: right;margin-right: 390px;font-size: 12px;font-weight: bold;">Address </b>
			<table class="receiver">
			
				 <thead>
				 <tr>			 
				<td >	 
				 Business Name: <b> <?php echo ucwords($client->business_name); ?></b>				
				<p>Phone : &nbsp;&nbsp;{{ $client->mobile ?? 'N/A' }}</p>
				<p>Email : &nbsp;&nbsp;&nbsp;&nbsp;{{ $client->email ?? 'N/A' }}</p>
				<p>PAN:   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $client->pan_no ?? 'N/A' }}</p>
				
		 		</td>
		 
		 		<td>	 
				<p>Name  : {{$client->sirName??''}}- {{$client->first_name??''}} &nbsp;{{ $client->last_name??''}}</p>
				<p> Address: {{$client->address??''}} </p>		 
				<p>GSTIN:&nbsp;&nbsp;&nbsp;&nbsp;{{ $client->gst_no ?? 'N/A' }}</p>
				</td>
		 
				</tr>
				</thead>
				 
			</table>	
			
			
		 
			<table class="inventory">
				<thead>
					<tr>
						<th><span >S.No</span></th>
						<th><span >Package</span></th>
						<th><span >Coins</span></th>
						<th><span >Rate(Per Package )</span></th>						
						<th><span >Amount</span></th>						
					</tr>
				</thead>
				<tbody>
					<tr style="height: 104px;">
						<td><span >1</span></td>					 
						<?php if($paymentprint->package_name=='Gold'){ ?>
						<td><span >Gold</span></td>
						<td><span ><?php  echo $paymentprint->coins_amt; ?> </span></td>	
						<td><span ><?php echo $paymentprint->paid_amount; ?></span></td>	
				
				<?php }else if($paymentprint->package_name=='Diamond'){ ?>
				 
						<td><span >Diamond</span></td>
						<td><span ><?php  echo $paymentprint->coins_amt; ?> </span></td>	
						<td><span ><?php echo $paymentprint->paid_amount; ?></span></td>
				<?php }if($paymentprint->package_name=='Platinum'){ ?>
					
					<td><span >Platinum</span></td>
						<td><span ><?php  echo $paymentprint->coins_amt; ?> </span></td>	
						<td><span ><?php echo $paymentprint->paid_amount; ?></span></td>
				
				<?php } ?>
						
					<td><?php echo $paymentprint->paid_amount; ?></td>	 
					</tr>
					<tr>
					<td colspan="3"></td>
					<td><span >GST</span></td>
					<td><span><?php echo $paymentprint->gst_tax?> INR</span></td>
					</tr>
					<tr>
					<td colspan="3"></td>
					<td><span >TDS</span></td>
					<td><span><?php echo $paymentprint->tds_amount?> INR</span></td>
					</tr>
				<tr>
					<td colspan="3"></td>
					<td><span >Total Amount</span></td>
					<td><span><?php echo $paymentprint->total_amount; ?> INR</span></td>
				</tr>
				
				<tr  style="height: 50px;">
					<td colspan="2" style="text-align:left;padding: 15px;">Total Invoice Value (In figure)<br>
					Invoice Value (In Words)
					</td>
					<td colspan="3" style="text-align:left;padding: 15px;"><b style="font-size: 12px;font-weight: bold;">Rs.<?php echo $paymentprint->total_amount; ?> INR</b><br>
					
					<b style="font-size: 12px;font-weight: bold;">Amt in Words:</b><?php echo $paymentprint->paid_amt_in_words; ?> 
					</td>
					 
				</tr>

				<tr><td colspan="5" style="text-align:left;margin-left:8px;font-size: 12px;font-weight: bold;">Payment Details ( Cheque )</td></tr>
				<tr>
				
				<td>Mode of Payment</td><td>Date</td><td>GST</td><td>TDS</td><td>Total Amount</td></tr>
				<tr style="height: 60px;">		
				<td><?php echo ucfirst($paymentprint->payment_mode); ?>/<?php if(!empty($paymentprint->payment_bank)) {?>
				<?php echo ucfirst($paymentprint->payment_bank); ?> 
				<?php }else  if(!empty($paymentprint->chq_card_no)) { ?>
				Cheque No <?php echo $paymentprint->chq_card_no ?>  
				<?php }else  if(!empty($paymentprint->pay_paytm)) { ?>
				 <?php echo $paymentprint->pay_paytm ?>  
				<?php }else  if(!empty($paymentprint->pay_neft)) { ?>
				  <?php echo $paymentprint->pay_neft ?>  
				<?php }else  if(!empty($paymentprint->pay_googlePay)) { ?>
				 <?php echo $paymentprint->pay_googlePay ?> <?php } ?> 				
				</td>				
				<td><?php echo date('d M-Y',strtotime($paymentprint->created_at)); ?></td>			
				<td><?php echo $paymentprint->gst_tax; ?> INR</td>	
				<td><?php echo $paymentprint->tds_amount; ?> INR</td>	
				<td><?php echo $paymentprint->total_amount; ?> INR</td>
				 
				
				</tr>
				
				
				</tbody>
			</table>
						
			<table class="amount">			 
				<tr>				 
					<td style="padding-top: 30px;"><span><b style="font-size: 12px;font-weight: bold;">Note:</b> This is a system-generated invoice; therefore, no signature is required.</span></td>
					<td class="td-amount" ><span>Autherised Signature</span></td>
				</tr>			
			</table>
			 
			 
			 
		</article>
		<aside width="90%">
			<h1><span ><b style="font-weight: 700;">Regd. Office:</b>G-13, Sector-3, Noida,Pin -201301 (UP), India.webite:-www.quickdials.com, cin:-G65HGGGAGAG</span></h1>
			<div class="thank" style="text-align:center">
				 
				 
			</div>
		</aside>
	</body>
</html>