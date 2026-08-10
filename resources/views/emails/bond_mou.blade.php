@extends('emails.layout')
    @section('title')
        Participation Agreement of Subscription
    @endsection

    @section('heading')
        Participation Agreement of Subscription
    @endsection

    @section('content')

        <div style="padding: 20px;">
            <h2>Dear {{ $client->full_name }}, your Participation Agreement of Subscription</h2>

            <p>Thank you for your investment. We&rsquo;ve attached your Participation Agreement of Subscription to this email. Kindly download to view.</p>

        </div>

    @endsection
