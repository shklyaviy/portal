<footer class="pf-footer">
    <div class="pf-footer-grid">
        <div>
            <h4>Кровельный центр «Портал»</h4>
            <p style="margin:0; line-height:1.55;">
                Кровля, фасады, заборы и комплектация с 1993 года.
                Новороссийск, Геленджик, Абинск.
            </p>
        </div>
        <div>
            <h4>Разделы</h4>
            <p style="margin:0; display:grid; gap:0.4rem;">
                <a href="{{ url('/catalog.html') }}">Каталог</a>
                <a href="{{ url('/service/kalkulyator-rascheta-krovli.html') }}">Калькулятор кровли</a>
                <a href="{{ url('/projects.html') }}">Объекты</a>
                <a href="{{ url('/contacts.html') }}">Контакты</a>
            </p>
        </div>
        <div>
            <h4>Связь</h4>
            <p style="margin:0; display:grid; gap:0.4rem;">
                <a href="tel:+79654644583">8-965-464-45-83</a>
                <a href="mailto:info@portalfirma.ru">info@portalfirma.ru</a>
            </p>
        </div>
    </div>
    <div class="pf-footer-copy">
        © {{ date('Y') }} Portalfirma · материалы для кровли и фасада
    </div>
</footer>
