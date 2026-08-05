@extends('emails.layout')
    @section('title')
        Bond Memorandum of Understanding
    @endsection

    @section('heading')
        Bond Memorandum of Understanding
    @endsection
    
    @section('content')
        
        <div style="padding: 20px;">
            <h2>Dear {{ $client->full_name }} your Bond Memorandum of Understanding</h2>
            
            <p>Thank you for your investment. We’ve attached your bond memorandum of understanding to this email. Kindly download to view.</p>
            
        </div>
        
    @endsection



