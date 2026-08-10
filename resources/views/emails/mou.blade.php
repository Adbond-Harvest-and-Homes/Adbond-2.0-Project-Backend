@extends('emails.layout')
    @section('title')
        Memorandum of Understanding
    @endsection

    @section('heading')
        Memorandum of Understanding
    @endsection

    @section('content')

        <div style="padding: 20px;">
            <h2>Dear {{ $client->full_name }} your Memorandum of Understanding</h2>

            <p>Thank you for your investment. We&rsquo;ve attached your investment memorandum of understanding to this email. Kindly download to view.</p>

        </div>

    @endsection
