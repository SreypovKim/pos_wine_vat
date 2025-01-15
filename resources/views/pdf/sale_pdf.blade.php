<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>Sale Detail</title>
       
   <style>
        @page {
         margin: 0cm 0cm;
      }

      body {
         background-size: cover;
         background-repeat: no-repeat;
         padding: 25px 25px 25px 25px;
         width: 100%;
         height: 100%;
         position: relative;
         margin: 0 auto;
         color: #555555;
         font-family: Arial, sans-serif;
         font-size: 14px;
         font-family: SourceSansPro;
         /* background-image: url("./images/background_invoice.jpg"); */

      }
      img{
         width: 100px;
         height: 90px;
         margin-bottom:-20px;
      }
      #Title-heading{
         text-align: center;
         font-size: 20px;
         color: black;
      }
      .col-head{
         width: 100vw;
         /* display: flex;
         justify-content: space-evenly; */
      }
      .leftDiv
         {
            width: 48%;
            float: left;
         }
         .rightDiv
         {
            width: 48%;
            float: right;
         }		
         #table{
            padding-top: 200;
            width: 100vw;
         }	
         #item{
            width: 100vw;
         }
         #details_inv>table {
   padding-right: 50px;
   padding-top: 170px;
	width: 100%;
	border-collapse: collapse;
	border-spacing: 0;
	margin-bottom: 1px;
   margin-top:1px;
}

#details_inv>table td {
	padding: 6px;
	background: white;
	text-align: center;
   border: solid 1px;
}

#details_inv>table th {
	padding: 6px;
	text-align: center;
	border-bottom: 1px solid #FFFFFF;
	color: #fff;
	background: royalblue;
	color: #fff;
	font-size: 13px;
	font-weight: bold;
   border: solid 1px;
}

#details_inv>table td {
	text-align: center;
}

#details_inv>table td h3 {
	color: #57B223;
	font-size: 1.2em;
	font-weight: normal;
	margin: 0 0 0.2em 0;
}

#details_inv>table .no {
	color: #FFFFFF;
	font-size: 1.6em;
	background: #57B223;
}

#details_inv>table .Ref {
	text-align: left;
	font-size: 16px!important;
}

#details_inv>table .Payment {
	text-align: right;
	font-size: 16px!important;
}

#details_inv>table .mode {
	text-align: center;
	font-size: 16px!important;
}

#details_inv>table td.unit,
#details_inv>table td.qty,
#details_inv>table td.total {
	font-size: 1.2em;
}

#details_inv>table tbody tr:last-child td {
	/* border: none; */
}

#details_inv>table tfoot td {
	padding: 10px 20px;
	background: #FFFFFF;
	border-bottom: none;
	font-size: 1.2em;
	white-space: nowrap;
	/* border-top: 1px solid #AAAAAA; */
   border: solid 1px;
}

#details_inv>table tfoot tr:first-child td {
	border-top: none;
}

#details_inv>table tfoot tr:last-child td {
	color: #57B223;
	font-size: 1.4em;
	border-top: 1px solid #57B223;
}

#details_inv>table tfoot tr td:first-child {
	border: none;
}

#thanks {
	font-size: 2em;
	margin-bottom: 50px;
	margin-top: 228px;
}

#signature {
	color: #777777;
	width: 100%;
	height: 30px;
	position: absolute;
	bottom: 30;
	padding: 8px 0;
	text-align: center;
}

#notices {
	padding-left: 6px;
	border-left: 6px solid #0087C3;
}

#notices .notice {
	font-size: 1.2em;
}

footer {
	color: #777777;
	width: 100%;
	height: 30px;
	position: absolute;
	bottom: 0;
	border-top: 1px solid #AAAAAA;
	padding: 8px 0;
	text-align: center;
}

#paiment {
	border: 2px solid;
	padding: 24px;
	width: 659px;
}
#total{
    /* padding-right: 500px; */
    margin-left: 590px;
    text-align: center;
    text: bold;
    border: solid 1px;
    width: 152px;
    margin-right: 10px;
}


   </style>
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
              <img src="{{asset('/images/'.$setting['logo'])}}">
         </div>
         <div id="Title-heading">
             Sale Detail {{$sale['Ref']}}
         </div>
      </header>
      <main>
         <div class="col-head">
            <div class="rightDiv">
               <p><strong>Customer Info:</strong></p>
                <table>
                    <tr>
                        <td>Name:</td>
                        <td>{{$sale['client_name']}}</td>
                    </tr>
                     <tr>
                        <td>Address :</td>
                        <td>{{$sale['client_adr']}}</td>
                    </tr>
                     <tr>
                        <td>Phone :</td>
                        <td>{{$sale['client_phone']}}</td>
                    </tr>
                     <tr>
                        <td>Mail :</td>
                        <td>{{$sale['client_email']}}</td>
                    </tr>
                    
                </table>

            </div>
             <div class="leftDiv">
               <p><strong>Company Info:</strong></p>
                 <table>
                    <tr>
                        <td>Date :</td>
                        <td>{{$sale['date']}}</td>
                    </tr>
                    <!-- <tr>
                        <td>Sale No :</td>
                        <td>{{$sale['Ref']}}</td>
                    </tr> -->
                    <tr>
                        <td>Name :</td>
                        <td>{{$setting['CompanyName']}}</td>
                    </tr>
                     <tr>
                        <td>Address:</td>
                        <td>{{$setting['CompanyAdress']}}</td>
                    </tr>
                     <tr>
                        <td>Phone :</td>
                        <td>{{$setting['CompanyPhone']}}</td>
                    </tr>
                    
                </table>
            </div>
           
         </div>
          <div id="details_inv">
            <table class="table-sm">
               <thead>
                  <tr>
                     <th>PRODUCT</th>
                     <th>UNIT PRICE</th>
                     <th>QUANTITY</th>
                     <th>DISCOUNT</th>
                     <th>TAX</th>
                     <th>TOTAL</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($details as $detail)    
                  <tr>
                     <td>{{$detail['code']}} ({{$detail['name']}})</td>
                     <td>$ {{$detail['price']}} </td>
                     <td>{{$detail['quantity']}}/{{$detail['unitSale']}}</td>
                     <td>$ {{$detail['DiscountNet']}} </td>
                     <td>$ {{$detail['taxe']}} </td>
                     <td>$ {{$detail['total']}} </td>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
         <div id="total">
            <table>
               <tr>
                  <td>Order Tax: </td>
                  <td>$ {{$sale['TaxNet']}} </td>
               </tr>
               <tr>
                  <td>Discount: </td>
                  <td>$ {{$sale['discount']}} </td>
               </tr>
               <tr>
                  <td>Shipping: </td>
                  <td>$ {{$sale['shipping']}} </td>
               </tr>
               <tr>
                  <td>Total:</td>
                  <td>$ {{$sale['GrandTotal']}} </td>
               </tr>
            </table>
         </div>
      </main>
      
   </body>
</html>