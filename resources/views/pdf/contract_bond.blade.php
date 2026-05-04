<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contract of Sale</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            line-height: 1.6;
            font-size: 13px;
            color: #000;
        }

        h2, h3, h4 {
            text-align: center;
            margin-bottom: 8px;
        }

        p {
            margin: 8px 0;
            text-align: justify;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th, td {
            border: 1px solid #333;
            padding: 8px;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            text-align: left;
        }

        .no-border {
            border: none;
        }

        .signature {
            margin-top: 40px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 18px;
            text-transform: uppercase;
        }

        .small {
            font-size: 12px;
        }
    </style>
</head>
<body>

    <h2>CONTRACT OF SALE</h2>

    <h4>BETWEEN</h4>
    <h3>ADBOND HARVEST AND HOMES LIMITED</h3>
    <p style="text-align:center;"><strong>(HOME DEVELOPER)</strong></p>

    <h4>AND</h4>
    <h3>{{ $bond_owner_name }}</h3>
    <p style="text-align:center;"><strong>(BOND HOME OWNERSHIP)</strong></p>

    <br>

    <p>
        THIS CONTRACT OF SALE is made this {{ $contract_day }} day of {{ $contract_month }} {{ $contract_year }}.
    </p>

    <p>
        BETWEEN <strong>ADBOND HARVEST AND HOMES LIMITED</strong> of No. 14, Allen Avenue,
        Ikeja, Lagos State, Nigeria (hereinafter referred to as the “Home Developer”)
        which expression shall where the context so admits include its successors-in-title
        and assigns) of the one part.
    </p>

    <p>
        AND <strong>{{ $bond_owner_name }}</strong> of {{ $bond_owner_address }}
        (hereinafter referred to as the “Bond Home Ownership” which expression shall where
        the context so admits include his or her successors-in-title and assigns) of the other part.
    </p>

    <h4>INTERPRETATION</h4>

    <table>
        <tr>
            <th style="width:30%;">Term</th>
            <th>Meaning</th>
        </tr>
        <tr>
            <td>Bond Home Ownership</td>
            <td>Means a purchaser of a home under this contract.</td>
        </tr>
        <tr>
            <td>Home Developer</td>
            <td>Means the seller of a home under this contract.</td>
        </tr>
        <tr>
            <td>Purchase Price</td>
            <td>Means the total cost of the home subject matter of this contract.</td>
        </tr>
        <tr>
            <td>Repossession</td>
            <td>
                Means the taking over of the home subject matter of this contract by the Home Developer
                from the Bond Home Ownership due to default.
            </td>
        </tr>
    </table>

    <p>
        The Home Developer has initiated a Private-driven Bond Home Ownership Scheme to match the
        increasing demand for housing and has developed housing communities situated at
        <strong>{{ $project_name }}</strong>, {{ $project_location }}.
    </p>

    <p>
        The Home Developer owns the entire interest in the home located at
        <strong>{{ $project_name }}</strong>, particulars of which are described and set out in
        Schedule 1 to this Contract of Sale (“the Home”).
    </p>

    <p>
        The Home Developer shall sell the home to the Bond Home Ownership and the Bond Home Ownership
        shall buy the home subject to the terms and conditions set forth in this contract.
    </p>

    <h4>DOCUMENTS FORMING PART OF THIS CONTRACT</h4>

    <table>
        <tr>
            <th style="width:8%;">#</th>
            <th>Document</th>
        </tr>
        <tr>
            <td>1</td>
            <td>Sales Receipt</td>
        </tr>
        <tr>
            <td>2</td>
            <td>Contract of Sale</td>
        </tr>
        <tr>
            <td>3</td>
            <td>Letter of Allotment</td>
        </tr>
        <tr>
            <td>4</td>
            <td>Provisional Survey Plan (of Land)</td>
        </tr>
        <tr>
            <td>5</td>
            <td>Provisional Deed of Assignment (of Land)</td>
        </tr>
    </table>

    <h4>PURCHASE PRICE</h4>

    <table>
        <tr>
            <th style="width:35%;">Total Purchase Price</th>
            <td>{{ number_format($purchase_price, 2) }}</td>
        </tr>
        @if($is_installment == 1)
            <tr>
                <th>Commitment Fee / Monthly Payment</th>
                <td>{{ number_format($monthly_payment, 2) }}</td>
            </tr>
            <tr>
                <th>Duration of Payment</th>
                <td>{{ $payment_duration }}</td>
            </tr>
        @endif
        <tr>
            <th>Payment Plan</th>
            <td>{{ $payment_plan }}</td>
        </tr>
    </table>
    
    @if($is_installment == 1)
        <p>
            In consideration of the sum of {{ number_format($monthly_payment, 2) }} per month being
            commitment fee of the purchase price in accordance with ADBOND policy, the Home Developer
            hereby acknowledges receipt and agrees to sell the property to the Bond Home Ownership.
        </p>
    @else
        <p>
            In consideration of the sum of {{ number_format($purchase_price, 2) }} being paid in accordance with ADBOND policy, the Home Developer
            hereby acknowledges receipt and agrees to sell the property to the Bond Home Ownership.
        </p>
    @endif
    
    @if($is_installment == 1)
        <h4>BALANCE PAYMENT</h4>

        <p>
            The Home Developer shall execute a Letter of Allotment after full payment of the total
            Purchase Price for the purpose of paying the balance of the purchase price on a monthly basis
            for {{ $payment_duration }}.
        </p>
    @endif

    <h4>CONDITIONS PRECEDENT</h4>

    <table>
        <tr>
            <th style="width:8%;">#</th>
            <th>Condition</th>
        </tr>
        <tr>
            <td>1</td>
            <td>Proof and/or source of income of the Bond Home Ownership.</td>
        </tr>
        <tr>
            <td>2</td>
            <td>
                An all-risk insurance policy to be taken out by the Bond Home Ownership in respect
                of the home during the term of the Sales Contract.
            </td>
        </tr>
    </table>

    <h4>HOME DEVELOPER’S WARRANTIES AND COVENANTS</h4>

    <table>
        <tr>
            <th style="width:8%;">#</th>
            <th>Provision</th>
        </tr>
        <tr>
            <td>1</td>
            <td>
                Upon full payment and execution of this contract, the Home Developer shall deliver
                the title documents of the home to the Bond Home Ownership.
            </td>
        </tr>
        <tr>
            <td>2</td>
            <td>
                The Home Developer warrants that there are no subsisting third-party claims,
                interests or encumbrances affecting the property.
            </td>
        </tr>
        <tr>
            <td>3</td>
            <td>
                For outright payment plans, the Home Developer shall deliver the home within
                {{ $outright_delivery_duration ?? 'specified' }} months after full payment.
            </td>
        </tr>
        <tr>
            <td>4</td>
            <td>
                For installment payment plans, the Home Developer shall deliver the home within
                18 months after completion of payment.
            </td>
        </tr>
    </table>

    <h4>BOND HOME OWNERSHIP WARRANTIES AND COVENANTS</h4>

    <table>
        <tr>
            <th style="width:8%;">#</th>
            <th>Provision</th>
        </tr>
        <tr>
            <td>1</td>
            <td>
                The Bond Home Owner warrants that prior to execution of this contract, he/she is
                not already allocated a Bond Home Ownership property within {{ $project_location }}.
            </td>
        </tr>
        <tr>
            <td>2</td>
            <td>
                The Bond Home Owner shall not transfer, pledge, charge, let or otherwise assign
                interest in the home to a third party without written consent from the Home Developer.
            </td>
        </tr>
        @if($bond_type == "co-ownership")
            <tr>
                <td>3</td>
                <td>
                    The Bond Home Owner agrees to share the Home with other families within the {{ $payment_duration }} months
                    of ownership annually as stated.
                </td>
            </tr>
        @endif
    </table>

    <h4>REPOSSESSION AND TERMINATION</h4>

    <p>
        Any false deposition in the Buyer’s details shall immediately entitle the Home Developer
        to terminate this contract and repossess the home.
    </p>

    <p>
        In the event of repossession, the Bond Home Owner shall only be entitled to a reallocation
        to another apartment/block within the next Housing Project of ADBOND subject to any penalty
        or deduction.
    </p>

    <p>
        Where the Bond Home Owner intends to terminate this contract, a resale-order will be advised
        and a new Bond Home Ownership purchaser will be sought before refund.
    </p>

    <h4>DEED OF ASSIGNMENT</h4>

    <p>
        Upon execution of this contract, the Bond Home Owner shall execute a Deed of Assignment
        with ADBOND subject to the documentation requirements of the Bond Home Ownership.
    </p>

    <h4>VACANT POSSESSION</h4>

    <p>
        The Home Developer shall grant vacant possession of the home to the Bond Home Owner
        immediately upon execution of the Deed of Assignment and certification of the home
        as fit for habitation.
    </p>

    <h4>FACILITY MANAGEMENT</h4>

    <p>
        A Facility Management company appointed by the Home Developer shall oversee the management
        and maintenance of the homes within {{ $project_location }}.
    </p>

    <p>
        The Bond Home Owner shall pay a monthly/quarterly/annual maintenance charge to the Facility
        Management company for services rendered.
    </p>

    <h4>NOTICES</h4>

    <p>
        Any notice or communication to any party shall be deemed sufficient if delivered by hand,
        courier service or email to the principal place of business or address earlier notified.
    </p>

    <h4>BENEFITS TO BOND HOME OWNERSHIP</h4>

    <table>
        <tr>
            <th style="width:8%;">#</th>
            <th>Benefit</th>
        </tr>
        <tr>
            <td>1</td>
            <td>Secured Community with CCTV monitoring.</td>
        </tr>
        <tr>
            <td>2</td>
            <td>Generational Lifetime Rental Income Management.</td>
        </tr>
        <tr>
            <td>3</td>
            <td>Annual Rental Earnings.</td>
        </tr>
        <tr>
            <td>4</td>
            <td>5% Discount on outright payments.</td>
        </tr>
        <tr>
            <td>5</td>
            <td>Installment payment options within 12/24/36 months.</td>
        </tr>
    </table>

    <h4>THE CONTRACT</h4>

    <p>
        This contract shall remain in effect until {{ $contract_end_date }} or such other period
        as may be agreed by the parties.
    </p>

    <h4>DISPUTE RESOLUTION</h4>

    <table>
        <tr>
            <th style="width:8%;">13.1</th>
            <td>
                Any dispute arising out of or relating to this contract shall be resolved exclusively
                by arbitration in accordance with the Arbitration Law of Nigeria.
            </td>
        </tr>
        <tr>
            <th>13.2</th>
            <td>
                The arbitration shall be conducted in Nigeria by a single arbitrator appointed by
                the President of the Ogun Court of Arbitration.
            </td>
        </tr>
        <tr>
            <th>13.3</th>
            <td>
                The decision of the arbitrator shall be final and binding on both parties.
            </td>
        </tr>
    </table>

    <br><br>

    <h4>SIGNED, SEALED AND DELIVERED</h4>

    <table>
        <tr>
            <th colspan="2" style="text-align:center;">FOR ADBOND HARVEST AND HOMES LIMITED</th>
        </tr>
        <tr>
            <td style="height:80px; width:50%; vertical-align:bottom;">
                ________________________________<br>
                DIRECTOR
            </td>
            <td style="height:80px; vertical-align:bottom;">
                ________________________________<br>
                EXECUTIVE SECRETARY
            </td>
        </tr>
    </table>

    <br>

    <table>
        <tr>
            <th colspan="2" style="text-align:center;">BOND HOME OWNERSHIP</th>
        </tr>
        <tr>
            <td style="height:80px; width:50%; vertical-align:bottom;">
                {{ $bond_owner_name }}
            </td>
            <td style="height:80px; vertical-align:bottom;">
                ________________________________<br>
                Signature
            </td>
        </tr>
    </table>

    <br>

    <h4>IN THE PRESENCE OF</h4>

    <table>
        <tr>
            <th style="width:30%;">Name</th>
            <td>-------------------------</td>
        </tr>
        <tr>
            <th>Occupation</th>
            <td>--------------------------</td>
        </tr>
        <tr>
            <th>Address</th>
            <td>---------------------------</td>
        </tr>
        <tr>
            <th>Signature</th>
            <td class="signature"></td>
        </tr>
        <tr>
            <th>Date</th>
            <td>--------------------------</td>
        </tr>
    </table>

</body>
</html>
