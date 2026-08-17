	
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Letter Of Happiness</title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div style="margin-left:10%; margin-right:10%;width: 80%; margin:0px">
        
        <div style="margin-left:70%; margin-bottom:20px; height:80px; width:30%;">
            <img src="https://api.adbondharvestandhomes.com/images/newLogo.png" width="150" height="50" style="margin:0px" />
        </div>
        
        <div style="margin-bottom:20px">
            <!-- <h5 style="margin: 0px">MR. AJIBOYE ADEYANJU AZEEZ,</h5> -->
            <h5 style="margin: 0px">{{ucfirst($name)}},</h5>
            @foreach($addressArr as $addr)
                {{$addr}}<br/>
            @endforeach
            <b style="margin-top:50px">{{$date}}</b>
            <!-- 28, Taiwo Street,<br/>
            Alamo Magboro,<br/>
            Ogun State.<br/> -->
        </div>
        <h3>CONGRATULATIONS!</h3>
        <P>The management and entire staff of Adbond Harvest and Homes Limited hereby congratulates you on
            the bold step you have taken to become an owner of {{$size}}Sqm of land suitable for our brand new
            initiative of Agro to Home investment opportunities thereof, by subscribing to the ADBOND’s project as
            your future agro to home developers.
        </P>
        <p>
        On our part, we are committed to our clear and well laid out plans of product marketing to the point of
        project incremental value in your investment and delivery. Kindly find below your subscription details:
        </p>
        <h3>SUBSCRIPTION DETAILS</h3>
        <table style="width: 100%;">
            <tr style="width: 100%;">
                <td>Project:</td>
                <td>{{$project}}</td>
            </tr>
            <tr style="width: 100%;">
                <td>Package:</td>
                <td>{{$package}}</td>
            </tr>
            <tr style="width: 100%;">
                <td>Location:</td>
                <td>{{$location}}</td>
            </tr>
            <tr style="width: 100%;">
                <td>Price:</td>
                <td>=N={{number_format($price)}}</td>
            </tr>
            <tr style="width: 100%;">
                <td>Total amount Paid:</td>
                <td>=N={{number_format($amount_paid)}}</td>
            </tr>
            <tr style="width: 100%;">
                <td>Size of Unit(s) subscribed:</td>
                <td>{{number_format($size)}}SQM</td>
            </tr>
            <tr style="width: 100%;">
                <td>Payment Date:</td>
                <td>{{$payment_date}}</td>
            </tr>
        </table>
        <h3>OTHER CHARGES</h3>
        <table>
            <tr>
                <td>Site Surveyor Allocation Fee:</td>
                <td>=N= Fully Discounted</td>
            </tr>
            <tr>
                <td>Location:</td>
                <td>{{$location}}</td>
            </tr>
            <tr>
                <td>Legal Documentation:</td>
                <td>=N= Fully Discounted</td>
            </tr>
            <tr>
                <td>Re-Survey & Registration:</td>
                <td>Price from Ministry of Land.</td>
            </tr>
        </table>
        <p style="margin-bottom:20px;">
            Once again, Congratulations and welcome to the Bond family
        </p>
        <div>
            <p style="margin-bottom: 0px;">
                Yours faithfully,<br/>
                For: <b>Adbond Harvest & Homes Limited.</b>
            </p>
            <img src="images/signature.jpeg" width="80" height="50" />
            <br/>
            <b>Secretary</b>
        </div>
    </div>
</body>
</html>
