@extends('emails.layout')
    @section('title')
        Corrected Document: Participation Agreement of Subscription
    @endsection

    @section('heading')
        Corrected Document
    @endsection

    @section('content')

        <div style="padding: 20px;">
            <h2>Dear {{ $client->full_name }},</h2>

            <p>We previously sent you a document for your BondHome subscription that did not match your package. Please disregard that earlier document &mdash; it was issued in error.</p>

            <p>Attached to this email are your correct documents: your <strong>Participation Agreement of Subscription</strong> and your <strong>Payment Receipt</strong>. Kindly download and keep these for your records.</p>

            <p>We apologize for the inconvenience and are happy to answer any questions you may have about your BondHome subscription.</p>
        </div>

    @endsection
