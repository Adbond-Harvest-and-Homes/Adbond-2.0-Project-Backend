@extends('emails.layout')
    @section('title')
        Bond Payout
    @endsection

    @section('heading')
        Bond Payout
    @endsection
    
    @section('content')
        
        <div style="padding: 20px;">
            <h2>Congratulations {{ $client->full_name }} your bond has earned a payout</h2>
            
            <p>Payout Amount: {{ $payout }}</p>
            
            <div style="background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;">
                Thank you for your investment
            </div>
        </div>
        
    @endsection