

@foreach($formData as $data)
    <div class="form-group">
        <div class="justify-content-between d-flex flex-wrap">
            <label class="form-label {{ $data->is_required == 'required' ? 'required' : null }}">{{ __($data->name) }}</label>
            @if($data->type == 'file')
                @foreach($userData as $file) 
                    @if($data->name == $file->name && $file->type == 'file' && $file->value)
                        <a href="{{ route('user.withdraw.download.attachment', encrypt($file->value)) }}">@lang('Download attachment')</a> 
                    @endif
                @endforeach
            @endif
        </div>
        @if($data->type == 'text')
            <input type="text"
                class="form-control form--control"
                name="{{ $data->label }}"
                @if($data->is_required == 'required') required @endif

                @foreach($userData as $text) 
                    @if($data->name == $text->name && $text->type == 'text')
                        value="{{ old($data->label) ?? $text->value }}"
                    @endif
                @endforeach
            >
        @elseif($data->type == 'textarea')
            @php
                $textareaVal = null;
                foreach($userData as $textarea){ 
                    if($data->name == $textarea->name && $textarea->type == 'textarea'){ 
                        $textareaVal = old($data->label) ?? $textarea->value;
                    }
                }
            @endphp
            <textarea
                class="form-control form--control"
                name="{{ $data->label }}"
                @if($data->is_required == 'required') required @endif
            >{{ $textareaVal }}</textarea>
        @elseif($data->type == 'select')
            <select
                class="form-control form--control form-select"
                name="{{ $data->label }}"
                @if($data->is_required == 'required') required @endif
            >
                <option value="">@lang('Select One')</option>
                @foreach($data->options as $item)
                    <option value="{{ $item }}"
                        @foreach($userData as $select)
                            @if($item == $select->value && $select->type == 'select')
                               @selected(true)
                            @endif
                        @endforeach
                    >
                        {{ __($item) }}
                    </option>
                @endforeach
            </select>
        @elseif($data->type == 'checkbox')
            @foreach($data->options as $option) 
                <div class="form-check">
                    <input
                        class="form-check-input exclude"
                        name="{{ $data->label }}[]"
                        type="checkbox"
                        value="{{ $option }}"
                        id="{{ $data->label }}_{{ titleToKey($option) }}"
                        @foreach($userData as $checkbox)  
                            @if(gettype($checkbox->value) == 'array')
                                @foreach($checkbox->value as $checkboxVal)
                                    @if($option == $checkboxVal && $checkbox->type == 'checkbox')
                                        @checked(true)
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    >
                    <label class="form-check-label" for="{{ $data->label }}_{{ titleToKey($option) }}">{{ $option }}</label>
                </div>
            @endforeach
        @elseif($data->type == 'radio')
            @foreach($data->options as $option)
                <div class="form-check">
                    <input
                    class="form-check-input exclude"
                    name="{{ $data->label }}"
                    type="radio"
                    value="{{ $option }}"
                    id="{{ $data->label }}_{{ titleToKey($option) }}"
                    @foreach($userData as $radio) 
                        @if($option == $radio->value && $radio->type == 'radio')
                            @checked(true)
                        @endif
                    @endforeach
                    >
                    <label class="form-check-label" for="{{ $data->label }}_{{ titleToKey($option) }}">{{ $option }}</label>
                </div>
            @endforeach
        @elseif($data->type == 'file')
            <input
                type="file"
                class="form-control form--control"
                name="{{ $data->label }}"

                @if($data->type == 'file')
                    @foreach($userData as $file) 
                        @if($data->name == $file->name && $file->type == 'file' && !$file->value && $data->is_required == 'required')
                            required
                        @endif
                    @endforeach
                @endif

                accept="@foreach(explode(',',$data->extensions) as $ext) .{{ $ext }}, @endforeach"
            >
            <pre class="text--base mt-1">@lang('Supported mimes'): {{ $data->extensions }}</pre>
        @endif
    </div>
@endforeach
