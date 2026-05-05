@props(['icon' => '', 'title' => ''])
<div class="home-body__item">
    <h3 class="home-body__title">
        <span class="icon"><i class="{{ $icon }}"></i></span>
        <span>{{ __($title) }}</span>
    </h3>
</div>
