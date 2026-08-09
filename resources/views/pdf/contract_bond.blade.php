<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bond Home Subscription and Participation Agreement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            line-height: 1.6;
            font-size: 13px;
            color: #000;
        }

        h1 {
            text-align: center;
            margin-bottom: 4px;
        }

        h2, h3, h4 {
            text-align: center;
            margin-bottom: 8px;
        }

        h4.section-heading {
            text-align: left;
            text-transform: uppercase;
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

        .center {
            text-align: center;
        }
    </style>
</head>
<body>

    <h1>BOND HOME SUBSCRIPTION AND PARTICIPATION AGREEMENT</h1>
    <p class="center"><strong>(Single Ownership and Co-Ownership)</strong></p>

    <h4>BETWEEN</h4>
    <p class="center">
        <strong>ADBOND HARVEST AND HOMES LTD</strong> of No. 14, Allen Avenue, Off Bamishile Street,
        Centage Plaza, Ikeja, Lagos State (hereinafter referred to as &ldquo;the Company&rdquo;, which
        expression shall, where the context so admits, include its successors and permitted assigns)
    </p>

    <h4>AND</h4>
    <p class="center">
        <strong>THE SUBSCRIBER, {{ $bond_owner_name }}</strong>, of {{ $bond_owner_address }}
        (hereinafter referred to as &ldquo;the Subscriber&rdquo;, which expression shall, where the
        context so admits, include his or her personal representatives, successors, and permitted assigns).
    </p>

    <p>
        The Company and the Subscriber are hereinafter collectively referred to as the &ldquo;Parties&rdquo;
        and individually as a &ldquo;Party.&rdquo;
    </p>

    <h4 class="section-heading">1. Whereas</h4>
    <p>
        A. The Company owns, operates, and administers the Bond Home Scheme, a fixed-yield residential
        housing subscription programme.
    </p>
    <p>
        B. The Subscriber has applied to participate in the Bond Home Scheme under
        {{ $bond_type == 'co-ownership' ? 'a Co-Ownership' : 'a Single Ownership' }} Subscription,
        subject to the terms of this Agreement.
    </p>
    <p>
        C. The Parties wish to record the terms governing the Subscriber&rsquo;s participation, the
        payment of Fixed Yield, the management of the Bond Home Unit, and all other rights and
        obligations arising from the Subscription.
    </p>

    <p>
        <strong>NOW THEREFORE</strong>, in consideration of the mutual covenants contained in this
        Agreement, the Parties agree as follows:
    </p>

    <h4 class="section-heading">2. Definitions</h4>
    <p>Unless the context otherwise requires:</p>
    <p>&ldquo;Agreement&rdquo; means this Bond Home Subscription and Participation Agreement, together with all Schedules attached to it.</p>
    <p>&ldquo;Bond Home Scheme&rdquo; means the fixed-yield residential housing subscription programme operated by the Company.</p>
    <p>&ldquo;Bond Home Unit&rdquo; means any residential housing unit designated by the Company for participation under the Bond Home Scheme.</p>
    <p>&ldquo;Certificate of Participation&rdquo; means the document issued by the Company confirming the Subscriber&rsquo;s participation in the Bond Home Scheme.</p>
    <p>&ldquo;Commencement Date&rdquo; means the date specified in Schedule A from which the Tenure and the Subscriber&rsquo;s entitlement to Fixed Yield commence.</p>
    <p>&ldquo;Co-Ownership Subscription&rdquo; means a Subscription held jointly by two or more Subscribers in the percentage interests specified in Schedule B.</p>
    <p>&ldquo;Fixed Yield&rdquo; means the agreed contractual percentage return payable to the Subscriber and recorded in Schedule A.</p>
    <p>&ldquo;Participation Rights&rdquo; means the contractual rights granted to the Subscriber under this Agreement.</p>
    <p>&ldquo;Single Ownership Subscription&rdquo; means a Subscription held solely by one Subscriber.</p>
    <p>&ldquo;Subscription&rdquo; means the Subscriber&rsquo;s contractual participation in the Bond Home Scheme under this Agreement.</p>
    <p>&ldquo;Subscription Amount&rdquo; means the amount payable by the Subscriber as specified in Schedule A.</p>
    <p>&ldquo;Subscriber&rdquo; means the person or persons admitted into the Bond Home Scheme pursuant to this Agreement.</p>
    <p>&ldquo;Tenure&rdquo; means the fixed period of ten (10) years commencing on the Commencement Date unless otherwise agreed in writing.</p>

    <h4 class="section-heading">3. Interpretation</h4>
    <p>3.1 Unless the context otherwise requires:</p>
    <p>(a) words importing the singular include the plural and vice versa;</p>
    <p>(b) references to one gender include every gender;</p>
    <p>(c) headings are for convenience only and shall not affect the interpretation of this Agreement;</p>
    <p>(d) references to any law include any amendment or replacement of that law.</p>

    <h4 class="section-heading">4. Purpose of the Bond Home Scheme</h4>
    <p>4.1 The Bond Home Scheme is established by the Company to provide eligible Subscribers with contractual participation rights in exchange for payment of the Subscription Amount.</p>
    <p>4.2 The Scheme entitles the Subscriber to receive the applicable Fixed Yield during the Tenure in accordance with this Agreement.</p>
    <p>4.3 Participation in the Bond Home Scheme does not constitute a sale or transfer of ownership of any Bond Home Unit unless expressly stated in a separate written agreement executed by the Company.</p>

    <h4 class="section-heading">5. Nature of the Subscription</h4>
    <p>5.1 Upon acceptance by the Company, the Subscriber shall acquire the contractual Participation Rights set out in this Agreement.</p>
    <p>5.2 The Subscription does not confer any legal, equitable, or beneficial ownership or other proprietary interest in any Bond Home Unit.</p>
    <p>5.3 The Company shall retain exclusive ownership, possession, management, and control of every Bond Home Unit throughout the Tenure unless otherwise agreed in writing.</p>

    <h4 class="section-heading">6. Eligibility</h4>
    <p>6.1 A person is eligible to participate in the Bond Home Scheme if he or she:</p>
    <p>(a) is at least eighteen (18) years of age;</p>
    <p>(b) has the legal capacity to enter into a binding contract under the laws of the Federal Republic of Nigeria; and</p>
    <p>(c) satisfies the Company&rsquo;s onboarding and compliance requirements.</p>
    <p>6.2 The Company may request such information or documents as it reasonably considers necessary to verify the Subscriber&rsquo;s identity and eligibility before accepting any Subscription.</p>

    <h4 class="section-heading">7. Subscription</h4>
    <p>7.1 A person may participate in the Bond Home Scheme by submitting a Subscription in the form prescribed by the Company and paying the applicable Subscription Amount.</p>
    <p>7.2 A Subscription may be made as:</p>
    <p>(a) a Single Ownership Subscription, where one Subscriber participates in the Scheme; or</p>
    <p>(b) a Co-Ownership Subscription, where two or more Subscribers jointly participate in the Scheme in the proportions specified in Schedule B.</p>
    <p>7.3 The Subscriber shall provide accurate and complete information in connection with the Subscription and shall promptly notify the Company of any material change to such information.</p>

    <h4 class="section-heading">8. Acceptance of Subscription</h4>
    <p>8.1 A Subscription shall become effective only upon the Company&rsquo;s acceptance.</p>
    <p>8.2 The Company may accept a Subscription after:</p>
    <p>(a) receipt and confirmation of the full Subscription Amount;</p>
    <p>(b) completion of all applicable verification and compliance requirements; and</p>
    <p>(c) issuance of the Subscriber&rsquo;s Official Payment Receipt, the executed Agreement, and the Certificate of Participation.</p>
    <p>8.3 Until the requirements of Clause 8.2 have been satisfied, no person shall be deemed a Subscriber under the Bond Home Scheme.</p>
    <p>8.4 The Company reserves the right to decline any Subscription that does not meet its eligibility, compliance, or operational requirements.</p>

    <h4 class="section-heading">9. Subscription Amount</h4>
    <p>9.1 The Subscription Amount payable by the Subscriber is <strong>&#8358;{{ number_format($purchase_price, 2) }}</strong>, as specified in Schedule A.</p>
    <p>9.2 The Subscription Amount shall be paid in the manner approved by the Company, on a {{ $payment_plan }} basis.</p>
    @if($is_installment == 1)
        <p>
            9.2.1 Where paid by Installments, the Subscriber shall pay &#8358;{{ number_format($monthly_payment, 2) }}
            per month for {{ $payment_duration }}.
        </p>
    @endif
    <p>9.3 Unless otherwise stated in writing, the Subscription Amount excludes applicable taxes, statutory charges, administrative fees, or other charges payable under Nigerian law.</p>

    <h4 class="section-heading">10. Commencement Date</h4>
    <p>10.1 The Subscription shall commence on the {{ $contract_day }} day of {{ $contract_month }}, {{ $contract_year }} (the &ldquo;Commencement Date&rdquo;), as specified in Schedule A.</p>
    <p>10.2 The Commencement Date shall be determined by the Company after the Subscription has been accepted in accordance with this Agreement.</p>
    <p>10.3 The Subscriber&rsquo;s entitlement to the Fixed Yield and the Tenure shall commence from the Commencement Date.</p>

    <h4 class="section-heading">11. Tenure</h4>
    <p>11.1 The Subscription shall remain in force for a fixed period of ten (10) years commencing on the Commencement Date, ending on {{ $contract_end_date }} unless otherwise terminated in accordance with this Agreement.</p>
    <p>11.2 Upon expiry of the Tenure, the Subscription shall automatically terminate, subject to the provisions of this Agreement relating to accrued rights and obligations.</p>
    <p>11.3 The Tenure may only be extended by a written agreement executed by the Parties.</p>

    <h4 class="section-heading">12. Fixed Yield</h4>
    <p>12.1 In consideration of the Subscription, the Company shall pay the Subscriber the Fixed Yield specified in Schedule A.</p>
    <p>12.2 The applicable Fixed Yield shall be determined by the Company based on the location, type, category, and value of the Bond Home Unit.</p>
    <p>12.3 The Fixed Yield applicable to the Subscription shall be stated in Schedule A and shall become binding upon the Company&rsquo;s acceptance of the Subscription.</p>
    <p>12.4 Different Bond Home Units may attract different Fixed Yield rates, and the Subscriber shall have no claim to the Fixed Yield applicable to any other Subscription.</p>
    <p>12.5 Unless otherwise agreed in writing, the Fixed Yield shall remain unchanged throughout the Tenure.</p>

    <h4 class="section-heading">13. Payout Options</h4>
    <p>13.1 At the time of Subscription, the Subscriber shall select one of the following payout options:</p>
    <p>(a) Annual Payout, under which the Fixed Yield shall be paid annually throughout the Tenure; or</p>
    <p>(b) Lump Sum Payout, under which the accumulated Fixed Yield shall be paid at the end of the Tenure.</p>
    <p>13.2 The selected payout option shall be recorded in Schedule A and shall form part of this Agreement.</p>
    <p>13.3 The Company shall make all payments to the bank account designated by the Subscriber or by any other payment method approved by the Company.</p>
    <p>13.4 A change to the selected payout option shall only be effective with the prior written approval of the Company.</p>

    <h4 class="section-heading">14. Closing Documentation</h4>
    <p>14.1 Upon acceptance of the Subscription, the Company shall issue the Subscriber with:</p>
    <p>(a) the executed Bond Home Subscription and Participation Agreement;</p>
    <p>(b) an Official Payment Receipt; and</p>
    <p>(c) a Certificate of Participation.</p>
    <p>14.2 The documents listed in Clause 14.1 shall constitute the official records of the Subscriber&rsquo;s participation in the Bond Home Scheme.</p>

    <h4 class="section-heading">15. Ownership of Bond Home Units</h4>
    <p>15.1 The Subscriber acknowledges that the Subscription creates only the contractual rights expressly provided under this Agreement.</p>
    <p>15.2 Nothing in this Agreement shall be construed as transferring legal, equitable, or beneficial ownership of any Bond Home Unit to the Subscriber.</p>
    <p>15.3 The Company shall remain the sole legal and beneficial owner of every Bond Home Unit throughout the Tenure and thereafter, except where otherwise agreed in writing.</p>

    <h4 class="section-heading">16. Management and Administration</h4>
    <p>16.1 The Company shall have the exclusive right to manage, operate, maintain, lease, insure, market, and administer every Bond Home Unit under the Bond Home Scheme.</p>
    <p>16.2 The Subscriber shall not interfere with the management or operation of any Bond Home Unit or exercise any right inconsistent with the Company&rsquo;s ownership and management.</p>
    <p>16.3 The Company may appoint agents, contractors, consultants, property managers, or other service providers as it considers necessary for the proper administration of the Bond Home Scheme.</p>

    <h4 class="section-heading">17. Rights and Obligations of the Company</h4>
    <p>17.1 The Company shall:</p>
    <p>(a) administer the Bond Home Scheme in accordance with this Agreement;</p>
    <p>(b) pay the Fixed Yield to the Subscriber in accordance with the selected payout option;</p>
    <p>(c) maintain accurate records of each Subscription;</p>
    <p>(d) issue the documents specified in Clause 14; and</p>
    <p>(e) comply with all applicable laws and regulatory requirements relating to the administration of the Bond Home Scheme.</p>
    <p>17.2 The Company reserves the right to:</p>
    <p>(a) verify all information and documents provided by the Subscriber;</p>
    <p>(b) appoint third parties to assist in the administration and management of the Bond Home Scheme;</p>
    <p>(c) suspend or reject any transaction that does not comply with this Agreement or applicable law; and</p>
    <p>(d) take any reasonable action necessary to protect the integrity and proper administration of the Bond Home Scheme.</p>

    <h4 class="section-heading">18. Rights and Obligations of the Subscriber</h4>
    <p>18.1 The Subscriber shall be entitled to:</p>
    <p>(a) receive the Fixed Yield in accordance with this Agreement;</p>
    <p>(b) receive the documents specified in Clause 14; and</p>
    <p>(c) transfer the Subscription only in accordance with this Agreement.</p>
    <p>18.2 The Subscriber shall:</p>
    <p>(a) pay the Subscription Amount in full;</p>
    <p>(b) provide complete and accurate information to the Company;</p>
    <p>(c) promptly notify the Company of any change to the Subscriber&rsquo;s contact or banking details;</p>
    <p>(d) comply with the terms of this Agreement; and</p>
    <p>(e) ensure that all funds used for the Subscription are derived from lawful sources.</p>

    <h4 class="section-heading">19. Transfer of Subscription</h4>
    <p>19.1 The Subscription is intended to remain in force for the full Tenure and may only be transferred in accordance with this Agreement.</p>
    <p>19.2 A Subscriber who wishes to exit the Bond Home Scheme before the expiry of the Tenure shall procure a suitable replacement Subscriber, subject to the prior written approval of the Company.</p>
    <p>19.3 The proposed replacement Subscriber shall:</p>
    <p>(a) satisfy the Company&rsquo;s eligibility and compliance requirements;</p>
    <p>(b) execute all documents required by the Company; and</p>
    <p>(c) agree to be bound by the terms of this Agreement.</p>
    <p>19.4 A transfer shall take effect only upon the Company&rsquo;s written approval and the completion of all required documentation.</p>
    <p>19.5 The Company shall not be obliged to repurchase, redeem, or refund the Subscription solely because the Subscriber wishes to exit the Bond Home Scheme before the expiry of the Tenure.</p>

    @if($bond_type == "co-ownership")
        <h4 class="section-heading">20. Co-Ownership</h4>
        <p>20.1 Where a Subscription is held by two or more Subscribers, each Subscriber shall hold a percentage interest as specified in Schedule B.</p>
        <p>20.2 The total percentage interests of all Co-Owners shall equal one hundred per cent (100%).</p>
        <p>20.3 All Fixed Yield payments shall be distributed in proportion to the percentage interests recorded in Schedule B.</p>
        <p>20.4 A Co-Owner who wishes to transfer his or her interest shall first offer that interest to the remaining Co-Owners by written notice.</p>
        <p>20.5 The remaining Co-Owners shall have thirty (30) Business Days to accept the offer.</p>
        <p>20.6 Where the remaining Co-Owners do not accept the offer within the prescribed period, the transferring Co-Owner may propose a replacement Subscriber, subject to the Company&rsquo;s prior written approval in accordance with Clause 19.</p>
    @endif

    <h4 class="section-heading">21. Representations and Warranties</h4>
    <p>21.1 The Company represents and warrants that it is duly incorporated under the laws of the Federal Republic of Nigeria and has the authority to enter into and perform this Agreement.</p>
    <p>21.2 The Subscriber represents and warrants that:</p>
    <p>(a) he or she has the legal capacity to enter into this Agreement;</p>
    <p>(b) all information provided to the Company is true, complete, and accurate;</p>
    <p>(c) the Subscription Amount is derived from lawful sources; and</p>
    <p>(d) he or she has read, understood, and voluntarily accepted the terms of this Agreement.</p>

    <h4 class="section-heading">22. Events of Default</h4>
    <p>22.1 A Subscriber shall be deemed to be in default where the Subscriber:</p>
    <p>(a) provides false, misleading, or fraudulent information;</p>
    <p>(b) submits forged or falsified documents;</p>
    <p>(c) breaches any material provision of this Agreement;</p>
    <p>(d) uses the Bond Home Scheme for any unlawful purpose; or</p>
    <p>(e) fails to comply with any applicable law relating to the Subscription.</p>
    <p>22.2 Where the default is capable of remedy, the Company may give the Subscriber written notice requiring the default to be remedied within fourteen (14) Business Days.</p>
    <p>22.3 Where the default is not remedied within the specified period, or where the default involves fraud or other unlawful conduct, the Company may terminate the Subscriber&rsquo;s participation in the Bond Home Scheme.</p>

    <h4 class="section-heading">23. Consequences of Default</h4>
    <p>23.1 Upon the occurrence of an Event of Default, the Company may:</p>
    <p>(a) suspend the Subscriber&rsquo;s participation;</p>
    <p>(b) withhold any payment pending investigation;</p>
    <p>(c) reject any proposed transfer of the Subscription; or</p>
    <p>(d) terminate this Agreement.</p>
    <p>23.2 Termination shall not affect any rights or obligations that accrued before the effective date of termination.</p>

    <h4 class="section-heading">24. Death and Succession</h4>
    <p>24.1 Where a Subscriber dies during the Tenure, the Subscriber&rsquo;s rights under this Agreement shall pass to the lawful personal representatives or beneficiaries of the Subscriber in accordance with applicable law.</p>
    <p>24.2 Before recognising any successor, the Company may require the production of Probate, Letters of Administration, or any other document reasonably required to establish entitlement.</p>
    <p>24.3 The Company may suspend payment of any entitlement until satisfactory evidence of entitlement has been provided.</p>

    <h4 class="section-heading">25. Reversion of Rights</h4>
    <p>25.1 Upon the expiry of the Tenure, the Subscriber&rsquo;s participation in the Bond Home Scheme shall automatically terminate.</p>
    <p>25.2 Upon such termination:</p>
    <p>(a) the Subscriber shall cease to be entitled to any further Fixed Yield;</p>
    <p>(b) all Participation Rights under this Agreement shall immediately determine; and</p>
    <p>(c) the Company shall retain full ownership and control of the Bond Home Unit.</p>
    <p>25.3 Except for any accrued but unpaid entitlement arising before the expiry of the Tenure, the Subscriber shall have no further claim against the Company in respect of the Subscription or the Bond Home Unit.</p>

    <h4 class="section-heading">26. Confidentiality</h4>
    <p>26.1 Each Party shall keep confidential all information obtained in connection with this Agreement and shall not disclose such information to any third party except:</p>
    <p>(a) with the prior written consent of the other Party;</p>
    <p>(b) where disclosure is required by law or a competent authority; or</p>
    <p>(c) where disclosure is reasonably required for the performance of this Agreement.</p>

    <h4 class="section-heading">27. Notices</h4>
    <p>27.1 Any notice or communication under this Agreement shall be in writing and shall be delivered by hand, courier, registered post, or electronic mail to the address last provided by the receiving Party.</p>
    <p>27.2 A Party shall promptly notify the other Party of any change to its contact details.</p>

    <h4 class="section-heading">28. Force Majeure</h4>
    <p>28.1 Neither Party shall be liable for any delay or failure in performing its obligations under this Agreement where such delay or failure is caused by an event beyond its reasonable control, including natural disasters, war, civil unrest, strikes, government actions, epidemics, or any other force majeure event.</p>
    <p>28.2 The affected Party shall notify the other Party as soon as reasonably practicable after becoming aware of the event.</p>

    <h4 class="section-heading">29. Limitation of Liability</h4>
    <p>29.1 The Company shall not be liable for any indirect, incidental, or consequential loss arising from the Subscriber&rsquo;s participation in the Bond Home Scheme, except where such loss results from the Company&rsquo;s fraud, wilful misconduct, or gross negligence.</p>
    <p>29.2 The Subscriber acknowledges that the Company&rsquo;s obligations are limited to those expressly set out in this Agreement.</p>

    <h4 class="section-heading">30. Entire Agreement</h4>
    <p>30.1 This Agreement constitutes the entire agreement between the Parties in respect of the Bond Home Scheme and supersedes all prior discussions, negotiations, representations, or understandings relating to its subject matter.</p>

    <h4 class="section-heading">31. Amendment</h4>
    <p>31.1 No amendment or variation of this Agreement shall be valid unless made in writing and signed by both Parties.</p>

    <h4 class="section-heading">32. Severability</h4>
    <p>32.1 If any provision of this Agreement is held to be invalid or unenforceable, the remaining provisions shall continue in full force and effect.</p>

    <h4 class="section-heading">33. Waiver</h4>
    <p>33.1 A failure or delay by either Party to exercise any right under this Agreement shall not constitute a waiver of that right.</p>
    <p>33.2 Any waiver, if intended, must be in writing.</p>

    <h4 class="section-heading">34. Binding Effect</h4>
    <p>34.1 This Agreement shall be binding upon and ensure to the benefit of the Parties and their respective personal representatives, successors, and permitted assigns.</p>

    <h4 class="section-heading">35. Governing Law and Dispute Resolution</h4>
    <p>35.1 This Agreement shall be governed by and construed in accordance with the laws of the Federal Republic of Nigeria.</p>
    <p>35.2 The Parties shall first seek to resolve any dispute arising from this Agreement through good-faith negotiations.</p>
    <p>35.3 Where the dispute is not resolved within thirty (30) days, it shall be referred to arbitration in accordance with the Arbitration and Mediation Act 2023. The seat of arbitration shall be Lagos, Nigeria, and the proceedings shall be conducted in English.</p>

    <br><br>

    <h4>EXECUTION</h4>
    <p><strong>IN WITNESS WHEREOF</strong>, the Parties have hereunto set their respective hands and seal the day and year first above written.</p>

    <h4 style="font-style: italic;">SIGNED, SEALED AND DELIVERED</h4>
    <p>By the within-named <strong>&ldquo;COMPANY&rdquo;</strong></p>

    <table>
        <tr>
            <td style="height:80px; width:50%; vertical-align:bottom;" class="no-border">
                ________________________________<br>
                DIRECTOR
            </td>
            <td style="height:80px; vertical-align:bottom;" class="no-border">
                ________________________________<br>
                DIRECTOR/SECRETARY
            </td>
        </tr>
    </table>

    <br>

    <h4 style="font-style: italic;">SIGNED, SEALED AND DELIVERED</h4>
    <p>By the within-named <strong>&ldquo;SUBSCRIBER&rdquo;</strong></p>

    <p>Name: {{ $bond_owner_name }}</p>
    <p>Signature: __________________________</p>

    <h4 style="text-decoration: underline; text-align:left;">IN THE PRESENCE OF</h4>

    <table>
        <tr>
            <th style="width:30%;">Name</th>
            <td>-------------------------------------------</td>
        </tr>
        <tr>
            <th>Address</th>
            <td>-------------------------------------------</td>
        </tr>
        <tr>
            <th>Occupation</th>
            <td>-------------------------------------------</td>
        </tr>
        <tr>
            <th>Signature</th>
            <td class="signature"></td>
        </tr>
    </table>

</body>
</html>
