	
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
        <h3 style="text-align: center;"><b><ul>CONGRATULATIONS ON THE COMPLETION OF YOUR ASSET PURCHASED.</ul></b></h3>

        <P>We the Management and Staff of ADBOND Harvest and Homes Limited congratulates 
            you on the completion of your Asset Purchased from our good self through the new 
            initiative of Agro to Home investment opportunities of Securing Future Space.
        </P>
        <p>
            Our core value is committed and well laid plans to the point of project incremental value 
            of your investment and delivery.
        </p>
        <p>
            Once again; Congratulations and welcome to the Bond family and Thank you for 
            Trusting the Brand.
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
