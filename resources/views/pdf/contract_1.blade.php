<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Offer Letter</title>
        <style>
            body { font-family: DejaVu Sans, sans-serif; line-height: 1.6; font-size: 13px; }
            h2 { text-align: center; }
        </style>
    </head>
    <body>

        <p><strong>NAME:</strong> {{ $name }}</p>
        <p><strong>ADDRESS:</strong> {{ $address }}</p>

        <h3>OFFER LETTER FOR THE SALE OF A PARCEL OF LAND MEASURING APPROXIMATELY {{ $plot_size }} SQUARE METRES</h3>

        <p>
            The {{ $estate_name }} (Product Name: {{ $product_name }}), a Residential Estate (Agro to Home
            Community project) situate at {{ $location }}, {{ $lga }}, {{ $state }}, and owned by 
            ADBOND HARVEST AND HOMES LIMITED, is pleased to offer you the above Property on the following terms and conditions::
        </p>

        <h4>PROPERTY LOCATION</h4>
        <p>{{ $product_name }}, {{ $lga }}, {{ $state }}</p>

        <h4>DESCRIPTION OF PROPERTY</h4>
        <p>A parcel of land measuring approximately {{ $plot_size }} square metres.</p>

        <h4>TOTAL AMOUNT PAYABLE</h4>
        <p>{{ $amount }}</p>

        <h4>PAYMENT PLAN</h4>
        <p>{{ $payment_plan }}</p>

        <h4>PAYMENT TERMS</h4>
        <p>{!! nl2br($payment_terms) !!}</p>

        <h4>USE</h4>
        <p>{{ $use_type }}</p>

        <!-- Continue replacing each section with variables exactly like this -->

        <h4>LEGAL / SURVEY FEE</h4>
        <p>
            A legal/survey fee of 13% of the total amount payable shall be paid before handover.
        </p>

        <!-- … Continue the rest of the document unchanged ... -->

        <h4>MEMORANDUM OF ACCEPTANCE</h4>

        <p>
            I/We, <strong>{{ $name }}</strong>, hereby accept the terms and conditions set forth in this Offer Letter.
        </p>

    </body>
</html>
