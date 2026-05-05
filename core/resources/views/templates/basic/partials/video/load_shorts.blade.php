@foreach ($shortVideos as $relatedVideo)

    <div class="video-wrapper">
        <video class="video-player" controls data-video_id="{{ $relatedVideo->id }}">
            <source src=" {{ route('short.path', encrypt($relatedVideo->id)) }}" type="video/mp4" />
        </video>

        <div class="action-container">
            <div class="cmn-button-item">
                <button class="like-button  button-item reactionBtn" data-video_id="{{ $relatedVideo->id }}" data-reaction="1">
                    @if ($relatedVideo->userReactions()->where('user_id', auth()->id())->where('is_like', Status::YES)->exists())
                        <i class="vti-like-fill reactionIcon"></i>
                    @else
                        <i class="vti-like reactionIcon"></i>
                    @endif
                </button>
                <span class="buton-text likeCount">{{ formatNumber($relatedVideo->userReactions()->like()->count()) }}</span>
            </div>
            <div class="cmn-button-item">
                <button class="dislike-button  button-item reactionBtn" data-video_id="{{ $relatedVideo->id }}" data-reaction="0">
                    @if ($relatedVideo->userReactions()->where('user_id', auth()->id())->where('is_like', Status::NO)->exists())
                        <i class="vti-dislike-fill reactionIcon"></i>
                    @else
                        <i class="vti-dislike reactionIcon"></i>
                    @endif
                </button>

            </div>
            <div class="cmn-button-item comment">
                <button class="button-item">
                    <i class="fa-solid fa-message"></i>
                </button>
            </div>
            <div class="cmn-button-item">
                <button class="button-item">
                    <i class="fa-solid fa-share"></i>
                </button>
                <span class="buton-text"></span>
            </div>
            <a class="action-container__thumb" href="{{ route('preview.channel', $relatedVideo->user->slug) }}">
                <img src="{{ getImage(getFilePath('userProfile') . '/' . $relatedVideo->user->image, isAvatar: true) }}" alt="@lang('image')">
            </a>
        </div>
    </div>
@endforeach
