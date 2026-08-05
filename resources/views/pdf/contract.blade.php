<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Offer Letter</title>
        <style>
            body { 
                font-family: DejaVu Sans, sans-serif; line-height: 1.5; font-size: 13px; color: #000; 
            }
            h2, h3 { 
                text-align: center; margin-bottom: 6px; 
            }
            h4 { 
                margin-top: 18px; margin-bottom: 6px; 
            }
            p { 
                margin: 6px 0; 
            }
            table { 
                width: 100%; border-collapse: collapse; margin-bottom: 12px; 
            }
            th, td { 
                border: 1px solid #333; padding: 8px; vertical-align: top; 
            }
            th { 
                background: #f2f2f2; text-align: left; 
            }
            .no-border { 
                border: none; padding: 0; 
            }
            .small { 
                font-size: 12px; 
            }
            .signature { 
                margin-top: 30px; 
            }
        </style>
    </head>
    <body>

        <!-- Header: Name & Address as a small table -->
        <table>
            <tr>
                <th style="width:15%;">NAME</th>
                <td>{{ $name }}</td>
            </tr>
            <tr>
                <th>ADDRESS</th>
                <td>{{ $address }}</td>
            </tr>
        </table>

        <h3>OFFER LETTER FOR THE SALE OF A PARCEL OF LAND MEASURING APPROXIMATELY {{ $size }} SQUARE METRES OF LAND</h3>

        <p>
        The <b>Adbond (Product Name: {{ $product_name }})</b>, a Residential Estate (Agro to Home Community development project)
        situate at {{ $location }}, and owned by ADBOND HARVEST AND HOMES LIMITED,
        is pleased to offer you the above Property on the following terms and conditions:
        </p>

        <!-- Property summary table -->
        <h4>PROPERTY SUMMARY</h4>
        <table>
            <tr>
                <th style="width:30%;">Property Location</th>
                <td>{{ $product_name }}, {{ $state }}</td>
            </tr>
            <tr>
                <th>Description of Property</th>
                <td>A parcel of land measuring approximately {{ $size }} square metres (The "Demised Property")</td>
            </tr>
            <tr>
                <th>Total Amount Payable</th>
                <td>{{ number_format($amount, 2) }} <span class="small">(Exclusive of taxes, fees and charges)</span></td>
            </tr>
            <tr>
                <th>Payment Plan</th>
                <td>{{ $payment_plan }}</td>
            </tr>
            <tr>
                <th>Use</th>
                <td>{{ $use_type }}</td>
            </tr>
        </table>

        <!-- Payment terms block (long text rendered safely) -->
        <h4>PAYMENT TERMS</h4>
        <p>{!! nl2br(e($payment_terms)) !!}</p>

        <!-- Fees table -->
        <h4>FEES & CHARGES</h4>
        <table>
            <tr>
                <th style="width:50%;">Legal / Survey Fee</th>
                <td>A legal/survey fee of {{ $legal_fee_percent ?? '13' }}% of the total amount payable shall be paid for documentation (legal) before the date of the handover. The Vendor agrees to make all documentation that will enable the Purchaser perfect his/her title including the provisional survey provided by the company.</td>
            </tr>
            <tr>
                <th>Statutory Charges & Land Registration</th>
                <td>Payable subject to Government's policy. Purchaser shall pay statutory charges on delivery of the property (up to {{ $statutory_charges_percent ?? '3' }}% of the cost of delivery). Land registration (survey, C of O etc.) shall be paid separately in the purchaser's name.</td>
            </tr>
            <tr>
                <th>Infrastructural Fees</th>
                <td>
                    Infrastructural Fees are Optional. Where purchaser decides to build, payment is mandatory (subject to review).<br>
                    Management Fee: After 36 months of Readiness for Allocation and customers do not show up, a Management Fee of {{ $management_fee_per_sqm ?? '#XX' }} per sqm for the {{ $product_name }} Project will be paid annually before allocation.<br>
                    Agro/Home Development Fee: If purchaser gets allocation during Agricultural Development Stage with payment of Agro Development Land Fee. <strong>Note:</strong> An additional Home Development Fee per sqm may apply later.
                </td>
            </tr>
        </table>

        <!-- Allocation schedule as table -->
        <h4>ALLOCATION</h4>
        <table>
            <tr>
                <th style="width:30%;">Frequency</th>
                <th>Deadlines</th>
                <th>Notes</th>
            </tr>
            <tr>
                <td>March Batch</td>
                <td>Deadline for sign-up: February 25th</td>
                <td>Allocation carried out on the 2nd & 4th Thursday</td>
            </tr>
            <tr>
                <td>September Batch</td>
                <td>Deadline for sign-up: August 25th</td>
                <td>Allocation carried out on the 2nd & 4th Thursday</td>
            </tr>
            <tr>
                <td>Villa Owners</td>
                <td>12th to 36th month after launch</td>
                <td>Villa owners with instant request can upgrade to already developing locations or place a resale order (24 months after sign-up). Allocation remains provisional until Final Allocation Letter issued prior to handover.</td>
            </tr>
        </table>

        <p><strong>Notice:</strong> Allocation at ADBOND is 100% Free and means Instant Development either at the Agricultural Development or Home Development. <strong>Implementation in Less than 21 days.</strong></p>

        <!-- Title transfer / checklist table -->
        <h4>TITLE TRANSFER / CONTRACT</h4>
        <p>The title document (to be prepared by the Vendor) shall be a Deed of Assignment or other appropriate transfer of title documents, Survey Plan & application for consent (if applicable) duly executed between the Vendor and the Purchaser.</p>

        <table>
            <tr>
                <th style="width:6%;">#</th>
                <th>Requirement</th>
            </tr>
            <tr>
                <td>1</td>
                <td>Payment of the total purchase price inclusive of fees, charges, taxes and government charges.</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Payment of the advised legal/survey fees.</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Due execution of the Estate's Handbook (if applicable) and any other required documents (Contract of Sale/Deed of Assignment), Finishing guidelines, Property Identification Form, Handover Contract, Move-In Contract, and MOU for Handover.</td>
            </tr>
        </table>

        <!-- Terms & conditions - keep as paragraphs -->
        <h4>TERMS AND CONDITIONS</h4>
        <p>
        The delivery timelines agreed by Parties may be affected by unforeseen circumstances, economic forces, pandemics, epidemics, social and security unrests, bureaucratic delays in governmental and regulatory approvals. The Vendor reserves the right to vary or terminate this Contract upon the occurrence of one or more of the events beyond its control which affects its ability to meet the delivery timelines and Project delivery cost.
        </p>

        <table>
            <tr>
                <th style="width:6%;">i.</th>
                <td><strong>Default in Payment:</strong> Compliance with the payment structure is a fundamental condition for the sale price offered. Failure to make payments as and when due may invalidate the offered sale price, in which case the Vendor reserves the right to review the price or cancel the offer.</td>
            </tr>
            <tr>
                <th>ii.</th>
                <td><strong>Transfer/Withdrawal:</strong> Payment made for land purchase is not refundable. The purchased land can only be transferred or placed on resale order. If a current client intends to reduce the number of plots, a legal fee of 10% deduction fees on each plot transferred applies.</td>
            </tr>
            <tr>
                <th>iii.</th>
                <td><strong>Resale Fees (Standard):</strong> 12% transaction fee charged while the seller pays another 10% for documentation legal fees and VAT of 7.5% — total 29.5% fee.</td>
            </tr>
            <tr>
                <th>iv.</th>
                <td><strong>Resale Fees (Villa Owner to Villa Owner):</strong> If Villa Owner sells to another Villa Owner by self, total charges will be 17.5% accruable to the company.</td>
            </tr>
            <tr>
                <th>v.</th>
                <td><strong>Resale Timeline:</strong> Resale order is not immediate; the instruction will be queued until sale is achievable.</td>
            </tr>
        </table>

        <p><strong>Force Majeure:</strong> This Contract shall be terminated by the Vendor in the event of riots, strikes, natural disasters, earthquakes, pandemics, epidemics and their consequences, government regulations that can impede or stop work, changes in Government policies resulting in the devaluation of the Naira, foreign exchange fluctuations and their consequences, changes in Governmental Town Planning or land laws and regulations, construction and ancillary matters.</p>

        <!-- Insurance & Common Areas -->
        <h4>INSURANCE</h4>
        <p>
        The Purchaser shall take out a comprehensive Insurance Policy to cover Fire, flood, theft and damage, which are associated occupancy risk in respect of the Property and shall do so within 7 Days after the handover exercise provided a copy of the Insurance Policy on Fire, flood, theft and damage is made available to the Vendor.<br>
        The Purchaser shall on an annualized basis renew the Insurance Policy and shall diligently forward the renewed Policy to the Vendor.
        </p>

        <h4>COMMON AREAS</h4>
        <p>
        The Purchaser understands and accepts that all common areas, green areas, recreational grounds and facilities belong to the Vendor and are the properties of the Vendor, provided by the Vendor for the common use and enjoyment of all the Home Owners and Residents in the Estate/Community. The Vendor reserves the right to take actions that it seems fit in respect of the common areas, green areas, recreational grounds and facilities. (Standard facilities to be provided includes: car park, water and power infrastructure and recreational area. Add-ons on recreational area are applicable as may be deemed fit by the vendor).
        </p>

        <!-- Payment details (bank details) -->
        <h4>PAYMENT DETAILS</h4>
        <table>
            <tr>
                <th style="width:30%;">Bank Name</th>
                <td>{{ $bank_name ?? 'UNITED BANK FOR AFRICA (UBA)' }}</td>
            </tr>
            <tr>
                <th>Account Number</th>
                <td>{{ $bank_account ?? '1019884249' }}</td>
            </tr>
            <tr>
                <th>Account Name</th>
                <td>{{ $bank_account_name ?? 'ADBOND HARVEST & HOMES LIMITED' }}</td>
            </tr>
        </table>

        <p>We look forward to receiving your signed acceptance of this offer.</p>

        <p><strong>Yours faithfully,<br>For: ADBOND HARVEST AND HOMES LIMITED</strong></p>

        <hr>

        <!-- Acceptance table/signature block -->
        <h3>MEMORANDUM OF ACCEPTANCE</h3>
        <table>
            <tr>
                <th style="width:30%;">Name of Purchaser</th>
                <td>{{ $name }}</td>
            </tr>
            <tr>
                <th>Acceptance</th>
                <td>I/We hereby accept the terms and conditions set forth in this Offer Letter for the purchase of the aforementioned parcel of land/property.</td>
            </tr>
            <tr>
                <th>Signature</th>
                <td class="signature">&nbsp;</td>
            </tr>
            <tr>
                <th>Date</th>
                <td>{{ $acceptance_date ?? '' }}</td>
            </tr>
        </table>

    </body>
</html>
