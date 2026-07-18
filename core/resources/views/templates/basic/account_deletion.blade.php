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

    <section class="py-60">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="account-deletion">
                        <p>
                            At {{ gs('site_name') }} you are always in control of your data. If you no longer
                            wish to use {{ gs('site_name') }}, you can request the permanent deletion of your
                            account and its associated personal data at any time using either of the options
                            below.
                        </p>

                        <h4 class="mt-4">Option 1 — Delete from within the app</h4>
                        <ol>
                            <li>Open the {{ gs('site_name') }} app and sign in to your account.</li>
                            <li>Go to <strong>Profile &rarr; Settings &rarr; Account</strong>.</li>
                            <li>Tap <strong>Delete Account</strong> and confirm.</li>
                        </ol>
                        <p>
                            Your account is deactivated immediately and permanently removed within 30 days.
                        </p>

                        <h4 class="mt-4">Option 2 — Request deletion from this page</h4>
                        <p>
                            If you can no longer access the app, submit the form below using the email address
                            registered to your account. We will verify the request and delete your account.
                        </p>

                        <form action="{{ route('account.deletion.request') }}" method="POST" class="mt-3">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="email">Account email address</label>
                                <input type="email" name="email" id="email" class="form-control"
                                    value="{{ old('email') }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="reason">Reason (optional)</label>
                                <textarea name="reason" id="reason" rows="3" class="form-control"
                                    maxlength="1000">{{ old('reason') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn--base">Request Account Deletion</button>
                        </form>

                        <h4 class="mt-5">What gets deleted</h4>
                        <p>When your deletion request is processed we permanently remove:</p>
                        <ul>
                            <li>Your profile and login credentials (name, username, email, password).</li>
                            <li>Your uploaded videos, playlists and channel content.</li>
                            <li>Your comments, reactions, subscriptions, watch history and watch-later list.</li>
                            <li>Device/push notification tokens linked to your account.</li>
                        </ul>

                        <h4 class="mt-4">What may be retained</h4>
                        <p>
                            Certain records may be kept for the period required by law or for legitimate
                            business purposes, such as transaction and payment records needed for tax,
                            accounting and fraud-prevention obligations. These are retained only as long as
                            legally required and are then deleted.
                        </p>

                        <h4 class="mt-4">Processing time</h4>
                        <p>
                            Deletion requests are actioned within 30 days. You will receive a confirmation
                            email once your account has been deleted. If you need help, contact us through the
                            <a href="{{ route('ticket.index') }}">support desk</a>.
                        </p>
                    </div>
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

        .account-deletion h4 {
            font-weight: 600;
        }

        .account-deletion ul,
        .account-deletion ol {
            padding-left: 20px;
        }

        .account-deletion li {
            margin-bottom: 6px;
        }
    </style>
@endpush
