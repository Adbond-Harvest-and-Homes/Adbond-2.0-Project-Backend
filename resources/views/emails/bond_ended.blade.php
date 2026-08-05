@extends('emails.layout')
    @section('title')
        Bond Investment Ended
    @endsection

    @section('heading')
        Bond Investment Ended
    @endsection
    
    @section('content')
        
        <div style="padding: 20px;">
            <h2>Dear {{ $client->full_name }} your bond Investment {{ $package->name }} has ended</h2>
            
            
            <div style="background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;">
                Thank you for your investment
            </div>
        </div>
        
    @endsection