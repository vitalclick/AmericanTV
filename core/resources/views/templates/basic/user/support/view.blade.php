@extends($activeTemplate . 'layouts.' . $layout)
@section('content')
    <div class="dashboard-content">

        <div class="row gy-4 justify-content-center">
            <div class="col-lg-8">
                <div class="card custom--card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                        <h5 class="card-title">
                            {{__($pageTitle)}}
                        </h5>
                        <a href="{{route('ticket.index')}}" class="btn ticket--btn">
                            <span class="icon">
                                <i class="far fa-list-alt"></i>
                            </span>
                            @lang('Ticket List')
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('ticket.reply', $myTicket->id) }}" method="post" enctype="multipart/form-data" >
                            @csrf
                        <div class="register">
                            <div class="row gy-4">
                                <div class="col-md-12">
                                    <label class="form--label required">@lang('Message')</label>
                                    <textarea name="message" required class="form--control"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form--label">@lang('Attachments')</label>
                                    <input type="file" name="attachments[]" class="form--control custom--file-input"
                                        multiple="" max="5" accept=".jpg, .jpeg, .png, .pdf, .doc, .docx">
                                    <small class="input-note-text style-two mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        @lang('Max 5 files can be uploaded | Maximum upload size is ' . convertToReadableSize(ini_get('upload_max_filesize')) . ' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')</small>

                                    <div class="atach-preview-wrapper input">
                                    
                                    </div>

                                    <div class="col-md-12 text-end">
                                        <button class="btn btn--base btn--sm">@lang('Submit Ticket')</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                    </div>
                </div>

                <div class="card custom--card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            @lang('Chat Converertion')
                        </h5>
                    </div>
                    <div class="card-body">

                        @foreach ($messages as $message)
                        <div class="chat-item  {{ $message->admin_id == 0 ? 'reply' : '' }}">
                            <span class="chat-item__thumb">
                                @php
                                            if ($message->admin_id) {
                                                $src = getImage(
                                                    getFilePath('adminProfile') . '/' . $message->admin->image,
                                                    getFileSize('adminProfile'),
                                                );
                                            } else {
                                                $src = getImage(
                                                    getFilePath('userProfile') .
                                                        '/thumb_' .
                                                        $message->ticket->user->image,
                                                );
                                            }
                                        @endphp

                                <img src="{{ $src }}" alt="profle"></a>
                            </span>
                            <div class="chat-item__content">
                                <p class="chat-item__name">
                                    {{ $message->admin ? $message->admin->name : $message->ticket->user->fullname }}
                                </p>
                                <p class="chat-item__time">
                                    <span>@lang('Created At'): </span>
                                    <span> <i class="far fa-clock"></i> {{ showdateTime($message->created_at) }}</span>
                                </p>

                                <p class="chat-item__message">{{ $message->message }}</p>

                                <div class="atach-preview-wrapper m-0">

                                    @foreach ($message->attachments as $attachment)
                                    <div class="atach-preview">
                                        <div class="atach-preview__left">
                                            <div class="atach-preview__image">
                                                @php $ext = pathinfo($attachment->attachment, PATHINFO_EXTENSION); @endphp
                                                <img src="{{ getImage(getFilePath('ticket') . '/' . (!in_array($ext, ['jpg', 'jpeg', 'png']) ? 'doc_type.png' : $attachment->attachment)) }}" alt="File">
                                            </div>
                                            <div class="atach-preview__contemt">
                                                <p class="atach-preview__title">@lang('attachments')</p>
                                                <p class="atach-preview__size">{{ fileSizeInB(getFilePath('ticket') . '/' . $attachment->attachment) }}</p>
                                            </div>
                                        </div>
                                        <div class="atach-preview__action">
                                            
                                            <a href="{{ route('ticket.download', encrypt($attachment->id)) }}" class="atach-icon">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    @endforeach
                                    
                                </div>

                            </div>
                        </div>
                        @endforeach

                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card custom--card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">@lang('Ticket Information')</h5>
                    </div>
                    <div class="card-body">
                        <div class="ticket-info">
                            <ul class="ticket-info-list">
                                <li class="ticket-info-item">
                                    <span class="ticket-info-title">@lang('Status')</span>
                                    <span class="ticket-info-text">
                                        @php echo $myTicket->statusBadge; @endphp
                                    </span>
                                </li>
                                <li class="ticket-info-item">
                                    <span class="ticket-info-title">@lang('Ticket ID')</span>
                                    <span class="ticket-info-text">#{{ $myTicket->ticket }}</span>
                                </li>
                                <li class="ticket-info-item">
                                    <span class="ticket-info-title">@lang('Priority')</span>
                                    <span class="ticket-info-text">
                                        @if ($myTicket->priority == Status::PRIORITY_LOW)
                                                <span class="badge badge--dark">@lang('Low')</span>
                                            @elseif($myTicket->priority == Status::PRIORITY_MEDIUM)
                                                <span class="badge  badge--warning">@lang('Medium')</span>
                                            @elseif($myTicket->priority == Status::PRIORITY_HIGH)
                                                <span class="badge badge--danger">@lang('High')</span>
                                            @endif
                                    </span>
                                </li>
                                <li class="ticket-info-item">
                                    <span class="ticket-info-title">@lang("Opened At")</span>
                                    <span class="ticket-info-text">
                                        <i class="far fa-clock"></i> {{ showDateTime($myTicket->created_at) }}
                                    </span>
                                </li>
                                
                                <li class="ticket-info-item">
                                    <span class="ticket-info-title">@lang('Last Reply')</span>
                                    <span class="ticket-info-text">
                                        <i class="far fa-clock"></i> {{ showDateTime($myTicket->last_reply) }}
                                    </span>
                                </li>
                                @if ($myTicket->status !== Status::TICKET_CLOSE)
                                <li class="ticket-info-item  w-100">
                                    <button   class="btn btn--danger btn--sm w-100 confirmationBtn"  data-question="Are you sure to close this ticket ?"  data-action="{{ route('ticket.close', $myTicket->id) }}">@lang('Close Ticket')</button>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    
    </div>

    <x-confirmation-modal frontend="true" />
@endsection
@push('style')
    <style>
        .input-group-text:focus {
            box-shadow: none !important;
        }

        .reply-bg {
            background-color: #ffd96729
        }

        .empty-message img {
            width: 120px;
            margin-bottom: 15px;
        }
    </style>
@endpush
@push('script')
    <script>
        "use strict";
        (function($) {
            const textarea = document.querySelector('.chat-form-input');
            textarea && textarea.addEventListener('input', function() {
                this.style.height = '48px';
                this.style.height = (this.scrollHeight) + 'px';
            });

            const fileInput = $(`[name="attachments[]"]`);
            let filesArray = [];

            fileInput.on('change', function() {
                $('.atach-preview-wrapper.input').empty();
                filesArray = Array.from(this.files); // Store File references

                const fileSize = $(this).attr('max');
                if (filesArray.length > fileSize) {
                    this.value = '';
                    notify('error', `You cannot upload more than ${fileSize} files`);
                    return false;
                }

                filesArray.forEach((file, index) => {
                    const reader = new FileReader();
                    const fileExtension = file.name.split('.').pop().toLowerCase();

                    reader.onload = function(e) {
                        let imageUrl = e.target.result;
                        const nonImageExtensions = ['doc', 'docx', 'pdf', 'xls', 'xlsx', 'txt',
                            'ppt',
                            'pptx'
                        ];

                        if (!['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                            imageUrl =
                                "{{ getImage(getFilePath('ticket') . '/' . 'doc_type.png') }}";
                        }

                        const html = `<div class="atach-preview" data-index="${index}">
                    <div class="atach-preview__left">
                        <div class="atach-preview__image">
                            <img src="${imageUrl}" alt="${file.name}">
                        </div>
                        <div class="atach-preview__contemt">
                            <p class="atach-preview__title">${file.name}</p>
                            <p class="atach-preview__size">${getFileSize(file.size)}</p>
                        </div>
                    </div>
                    <div class="atach-preview__action">
                        <a href="javascript:void(0);" class="atach-icon delete-icon">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </div>
            `;

                        $('.atach-preview-wrapper.input').append(html);
                    };

                    reader.readAsDataURL(file);
                });

                $(document).on('click', '.delete-icon', function() {
                    const index = $(this).closest('.atach-preview').data('index');
                    filesArray.splice(index, 1);
                    const dataTransfer = new DataTransfer();
                    filesArray.forEach(file => dataTransfer.items.add(file));
                    fileInput[0].files = dataTransfer.files;
                    $(this).closest('.atach-preview').remove();
                });
            });

            function getFileSize(size) {
                if (size >= 1048576) {
                    return (size / 1048576).toFixed(2) + ' MB';
                } else if (size >= 1024) {
                    return (size / 1024).toFixed(2) + ' KB';
                } else {
                    return size + ' bytes';
                }
            }

        })(jQuery);
    </script>
@endpush
