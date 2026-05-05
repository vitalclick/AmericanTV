@extends($activeTemplate . 'layouts.app')
@section('app')
    <div class="breadcrumb">
        <div class="container">
            <div class="breadcrumb-wrapper">
                <a class="link" href="{{ route('home') }}">
                    <img src="{{ siteLogo() }}" alt="logo">
                </a>
            
                    <h2 class="breadcrumb-list__item mt-3 mb-0">{{ __($pageTitle) }}</h2>
            
            </div>
        </div>
    </div>
    </div>
    <section class="py-60">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    @php
                        echo $policy->data_values->details;
                    @endphp
                </div>
            </div>
        </div>
    </section>
@endsection

@push('style')
    <style>
        .breadcrumb {
            margin-bottom: 0;
            background: hsl(var(--body-background));
            text-align: center;
            padding: 30px 0;
            box-shadow: 0 0 10px hsl(var(--white)/.1);
        }

        .breadcrumb-list {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }
    </style>
@endpush
