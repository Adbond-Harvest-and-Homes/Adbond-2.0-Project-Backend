	
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
        }
        
        table td {
            text-align: center;
        }
    </style>
    
</head>
<body>
    <div style="margin-left:5%; margin-right:10%;width: 80%; margin:0px">
        
        <div style="margin-bottom:20px">
            <div style="float:right; height:80px; width:30%;">
                <img src="{{asset('images/'.$image)}}" width="150" height="80" style="margin:0px" />
            </div>
            <div>
                <h1 style="margin-bottom:0px">Sales e-receipt</h1>
                <p>
                    Date: {{$date}}<br/>
                    RECEIPT# {{$receiptNo}}
                </p>
            </div>
        </div>

        <div style="margin-top:10px; margin-left:50%; width: 50%">
            SOLD TO
            <div style="float:right">
                <b>{{$name}}</b><br/>
                @if(isset($address1) && $address1 != '') {{$address1}} @endif<br/>
                @if(isset($address2) && $address2 != '') {{$address2}} @endif<br/>
                @if(isset($address3) && $address3 != '') {{$address3}} @endif<br/>
            </div>
        </div>

        <div style="margin-top:100px; width:120%; height:350px; border:thin solid #000">
            <div style="margin-left:5%; margin-top:5px;">
                <b>Payment Method:</b> {{$paymentMethod}}<br/>
                <b>Project:</b> {{$project}}<br/>
                <b>Package:</b> {{$package}}<br/>
                <b>Amount Paid(N):</b> {{number_format($currentAmount)}}<br/>
            </div>
            <hr/>
            <table border="1" style="width:100%; margin-top:15px; border-top:thin solid #000; border-collapse: collapse">
                <thead>
                    <th>QTY</th>
                    <th>Description</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>Total</th>
                </thead>
                <tbody>
                    <tr>
                        <td> {{ $units }} </td>
                        <td>
                            {{number_format($size)}}SQM of {{$project}} {{$package}}
                            <br/><br/>
                            Legal Documentation Fee
                            <br/><br/>
                            Land Registration Fee/ C of O
                            <br/><br/>
                        </td>
                        <td>
                            {{number_format($price)}}
                            <br/><br/>
                            0.00
                            <br/><br/>
                            0.00
                            <br/><br/>
                        </td>
                        <td>
                            {{$discount}}%
                            <br/><br/>
                            
                            <br/><br/>
                            
                            <br/><br/>
                        </td>
                        <td>
                            {{$amount}}
                            <br/><br/>
                            0.00
                            <br/><br/>
                            0.00
                            <br/><br/>
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan="4" style="text-align:right; padding-right: 10%">Total Amount(N)</td>
                        <td>{{number_format($amount)}}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:right; padding-right: 10%">Total Amount Paid(N)</td>
                        <td>{{number_format($amountPaid)}}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:right; padding-right: 10%">Outstanding Balance(N)</td>
                        <td>{{number_format($balance)}}</td>
                    </tr>
                    
                </tbody>
            </table>

            <div style="width:100%; margin-top:30px; text-align:center">
                <h2>Thanks for your patronage</h2>
            </div>
        </div>
        
    </div>
</body>
</html>
