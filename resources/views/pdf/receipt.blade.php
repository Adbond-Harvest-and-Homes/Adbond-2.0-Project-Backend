<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Sales e-Receipt</title>
        <style>
            body { 
                font-family: DejaVu Sans, sans-serif; 
                line-height: 1.4; 
                font-size: 13px; 
                color: #000; 
            }
            h2, h3 { 
                text-align: center; 
                margin-bottom: 10px; 
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 12px; 
            }
            th, td { 
                border: 1px solid #333; 
                padding: 8px; 
                vertical-align: top; 
            }
            th { 
                background: #f2f2f2; 
            }
            .no-border { 
                border: none; 
            }
            .right { 
                text-align: right; 
            }
            .center { 
                text-align: center; 
            }
            .header-table td { 
                border: none; 
                padding: 4px 0; 
                margin-top: 20px;
            }
        </style>
    </head>
    <body>

        <!-- <div style="float:right; height:80px; width:30%;">
            <img src="{{asset('images/'.$image)}}" width="150" height="80" style="margin:0px" />
        </div>

        <h2>Sales e-Receipt</h2>

        -- Header info in a table --
        <table class="header-table">
            <tr>
                <td><strong>Date:</strong> {{ $date }}</td>
                <td class="right"><strong>Receipt No:</strong> {{ $receiptNo }}</td>
            </tr>
        </table> -->

        <!-- Header with logo and date/receipt number -->
        <table style="width:100%; border:none; margin-bottom:10px;">
            <tr style="border:none;">
                <td style="width:70%; border:none; vertical-align:top;">
                    <h2 style="margin-top:0;">Sales e-Receipt</h2>

                    <table class="header-table">
                        <tr>
                            <td><strong>Date:</strong> {{ $date }}</td>
                            <td class="right"><strong>Receipt No:</strong> {{ $receiptNo }}</td>
                        </tr>
                    </table>
                </td>

                <td style="width:30%; text-align:right; border:none;">
                    <img src="{{ asset('images/'.$image) }}" width="150" height="80" 
                        style="margin:0; display:block;" />
                </td>
            </tr>
        </table>

        <!-- Buyer Information -->
        <table>
            <tr>
                <th style="width:25%;">SOLD TO: NAME</th>
                <td>{{ $name }}</td>
            </tr>

            <tr>
                <th>ADDRESS</th>
                <td>
                    @if(isset($address1) && $address1 != '') {{$address1}} @endif<br/>
                    @if(isset($address2) && $address2 != '') {{$address2}} @endif<br/>
                    @if(isset($address3) && $address3 != '') {{$address3}} @endif<br/>
                </td>
            </tr>

            <tr>
                <th>STATE</th>
                <td>{{ $clientState }}</td>
            </tr>

            <tr>
                <th>COUNTRY</th>
                <td>{{ $clientCountry }}</td>
            </tr>

            <tr>
                <th>Payment Method</th>
                <td>{{ $paymentMethod }}</td>
            </tr>

            <tr>
                <th>Site Name</th>
                <td>{{ $project }}</td>
            </tr>
        </table>

        <!-- Product details table -->
        <h3>Transaction Details</h3>

        <table>
            <tr>
                <th style="width:15%;">Size (SQM)</th>
                <th>Description</th>
                <th style="width:20%;">Unit Price (#)</th>
                <th style="width:15%;">Discount (%)</th>
                <th style="width:20%;">Total (#)</th>
            </tr>

            <tr>
                <td>{{ $size }}</td>
                <td>
                    {{ $size }}Sqm land at Adbond Agro and Homes
                </td>
                <td>{{ number_format($price, 2) }}</td>
                <td>{{ $discount }}%</td>
                <td>{{ number_format($currentAmount, 2) }}</td>
            </tr>
        </table>

        <!-- Additional fees -->
        <table>
            <tr>
                <th style="width:40%;">Project Name & State:</th>
                <td>{{ $project_name_state }}</td>
            </tr>

            <tr>
                <th>Legal Documentation Fee</th>
                <td>0.00</td>
            </tr>

            <tr>
                <th>Land Registration Fee / C of O</th>
                <td>0.00</td>
            </tr>

            <tr>
                <th>Bank Deposit / Transfer of</th>
                <td>#{{ number_format($currentAmount, 2) }}</td>
            </tr>
        </table>

        <!-- Totals -->
        <table>
            <tr>
                <th style="width:40%;">Total Amount (#)</th>
                <td>{{ number_format($amount, 2) }}</td>
            </tr>

            <tr>
                <th>Total Amount Paid (#)</th>
                <td>{{ number_format($amountPaid, 2) }}</td>
            </tr>

            <tr>
                <th>Outstanding Balance (#)</th>
                <td>{{ number_format($balance, 2) }}</td>
            </tr>
        </table>

        <h3>Thanks for your patronage</h3>

    </body>
</html>
