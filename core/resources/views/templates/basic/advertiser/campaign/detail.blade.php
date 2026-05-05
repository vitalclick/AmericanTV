@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
             <div class="content-top">
            <div class="left">
                <h4 class="title">{{ __($pageTitle) }}</h4>
            </div>
           
           
        </div>

    <div class="campaign-table mt-4">
       
        @include('Template::advertiser.campaign.detail_table', ['campaign' => $campaign])
        </div>     
    </div>

@endsection


