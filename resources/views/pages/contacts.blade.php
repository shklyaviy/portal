@extends('layouts.app')

@section('content')
    <section class="pf-page-hero pf-reveal">
        <div class="pf-crumbs"><a href="{{ url('/') }}">Главная</a><span>/</span><span>Контакты</span></div>
        <h1>{{ $title ?? 'Контакты' }}</h1>
        <p>Офисы в Новороссийске, Геленджике и Абинске. Оставьте заявку — перезвоним в рабочий день.</p>
    </section>

    <section class="pf-section pf-reveal" id="form">
        <div class="pf-product-grid">
            <div>
                @if(!empty($bodyHtml))
                    <div class="pf-content">{!! $bodyHtml !!}</div>
                @else
                    <h2 class="pf-section-title">Как связаться</h2>
                    <p class="pf-lead">Новороссийск · Геленджик · Абинск</p>
                    <p><a href="tel:+79654644583">8-965-464-45-83</a></p>
                    <p><a href="mailto:info@portalfirma.ru">info@portalfirma.ru</a></p>
                @endif
            </div>
            <aside class="pf-side-card">
                <h3 style="margin:0 0 0.75rem; font-family:var(--pf-display);">Оставить заявку</h3>
                <form class="pf-form" method="post" action="{{ route('leads.store') }}">
                    @csrf
                    <input type="hidden" name="source" value="contacts">
                    <input type="text" name="name" placeholder="Имя" required value="{{ old('name') }}">
                    <input type="text" name="phone" placeholder="Телефон" required value="{{ old('phone') }}">
                    <textarea name="comment" rows="3" placeholder="Комментарий">{{ old('comment') }}</textarea>
                    <button class="pf-btn pf-btn-primary" type="submit" style="width:100%;">Отправить</button>
                </form>
            </aside>
        </div>
    </section>
@endsection
