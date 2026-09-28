@extends('layouts.app')

@section('content')
<section class="pf-section" x-data="roofCalculator()" x-cloak>
    <p class="pf-muted" style="margin-top:0;">Кровельный калькулятор</p>
    <h1>{{ $h1 ?? 'Калькулятор расчёта кровли' }}</h1>
    <p class="pf-muted">Выберите форму крыши, материал и внесите размеры по схеме.</p>

    <div style="margin-top:1.5rem;">
        <h2 style="font-size:1.1rem;">1. Выберите тип кровли</h2>
        <div class="pf-grid" style="margin-top:0.75rem;">
            <template x-for="roof in roofs" :key="roof.id">
                <button type="button" class="pf-card-link" style="cursor:pointer; text-align:left;"
                    :style="roofType === roof.id ? 'border-color:var(--pf-primary); box-shadow:0 8px 20px rgba(168,8,8,0.16);' : ''"
                    @click="selectRoof(roof)">
                    <img :src="roof.icon" :alt="roof.label" style="height:56px; width:100%; object-fit:contain; margin-bottom:0.5rem;">
                    <div style="font-weight:600;" x-text="roof.label"></div>
                </button>
            </template>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <h2 style="font-size:1.1rem;">2. Материал</h2>
        <div class="pf-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); margin-top:0.75rem;">
            <template x-for="(item, key) in materials" :key="key">
                <button type="button" class="pf-card-link" style="cursor:pointer; text-align:left;"
                    :style="material === key ? 'border-color:var(--pf-secondary);' : ''"
                    @click="material = key">
                    <div style="font-weight:600;" x-text="item.label"></div>
                    <div class="pf-muted" style="margin-top:0.35rem;" x-text="formatNumber(item.pricePerM2) + ' ₽ / м²'"></div>
                </button>
            </template>
        </div>
    </div>

    <div style="display:grid; gap:1.25rem; margin-top:1.5rem;" class="calc-layout">
        <div>
            <div style="display:flex; justify-content:space-between; gap:0.75rem; align-items:center;">
                <h2 style="font-size:1.1rem; margin:0;">3. Размеры по схеме</h2>
                <span class="pf-muted" x-text="currentRoof.label"></span>
            </div>
            <div class="pf-grid" style="margin-top:0.75rem;">
                <template x-for="field in currentRoof.fields" :key="field.code">
                    <label>
                        <span class="pf-muted" style="display:flex; gap:0.5rem; align-items:center; margin-bottom:0.25rem;">
                            <span style="display:inline-flex; min-width:2rem; height:1.75rem; align-items:center; justify-content:center; border:1px solid var(--pf-line); border-radius:8px; font-weight:700;" x-text="field.code.toUpperCase()"></span>
                            <span x-text="field.label"></span>
                        </span>
                        <input type="text" inputmode="decimal" placeholder="0"
                            :value="values[roofType + ':' + field.code] || ''"
                            @focus="activeField = field.code"
                            @input="values[roofType + ':' + field.code] = $event.target.value">
                    </label>
                </template>
            </div>

            <div style="display:grid; gap:0.75rem; margin-top:1rem; grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                <label>
                    <span class="pf-muted">Запас на подрезку, %</span>
                    <input type="text" inputmode="decimal" x-model="wastePercent">
                </label>
                <div style="display:grid; gap:0.5rem;">
                    <label style="display:flex; gap:0.5rem; align-items:center; border:1px solid var(--pf-line); border-radius:12px; padding:0.6rem 0.8rem; background:#fff;">
                        <input type="checkbox" x-model="withOverhang"> Карнизные свесы
                    </label>
                    <label style="display:flex; gap:0.5rem; align-items:center; border:1px solid var(--pf-line); border-radius:12px; padding:0.6rem 0.8rem; background:#fff;">
                        <input type="checkbox" x-model="withGutter"> Водосточная система
                    </label>
                    <label style="display:flex; gap:0.5rem; align-items:center; border:1px solid var(--pf-line); border-radius:12px; padding:0.6rem 0.8rem; background:#fff;">
                        <input type="checkbox" x-model="withInsulation"> Утепление
                    </label>
                </div>
            </div>

            <button type="button" class="pf-btn" style="margin-top:1rem;" @click="showResult = true">Рассчитать</button>
        </div>

        <aside class="pf-section" style="margin:0;">
            <p class="pf-muted" style="margin-top:0;">Подсказка по замерам</p>
            <h3 style="margin:0 0 0.75rem;" x-text="'Схема ' + currentRoof.label.toLowerCase()"></h3>
            <div style="border:1px solid var(--pf-line); border-radius:14px; background:#fff; padding:0.75rem;">
                <img :src="schemeSrc" :alt="'Схема ' + currentRoof.label" style="display:block; width:100%; height:240px; object-fit:contain;">
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-top:0.75rem;">
                <template x-for="field in currentRoof.fields" :key="'chip-'+field.code">
                    <button type="button" @click="activeField = field.code"
                        style="padding:0.35rem 0.7rem; border-radius:8px; border:1px solid var(--pf-line); background:#fff; font-size:0.75rem; cursor:pointer;"
                        :style="activeField === field.code ? 'border-color:var(--pf-primary); color:var(--pf-primary);' : ''"
                        x-text="field.code.toUpperCase()"></button>
                </template>
            </div>
        </aside>
    </div>

    <aside class="pf-section" style="margin-top:1.5rem;" x-show="showResult" x-cloak>
        <p class="pf-muted" style="margin-top:0;">Результат</p>
        <h2 style="margin-top:0;">Ориентировочная смета</h2>
        <dl style="margin:0; display:grid; gap:0.5rem;">
            <div style="display:flex; justify-content:space-between; gap:1rem;"><dt class="pf-muted">Проекция крыши</dt><dd style="margin:0; font-weight:600;" x-text="formatNumber(result.projectionArea) + ' м²'"></dd></div>
            <div style="display:flex; justify-content:space-between; gap:1rem;"><dt class="pf-muted">Площадь кровли</dt><dd style="margin:0; font-weight:600;" x-text="formatNumber(result.roofArea) + ' м²'"></dd></div>
            <div style="display:flex; justify-content:space-between; gap:1rem;"><dt class="pf-muted">С учётом запаса</dt><dd style="margin:0; font-weight:600;" x-text="formatNumber(result.roofAreaWithWaste) + ' м²'"></dd></div>
            <div style="display:flex; justify-content:space-between; gap:1rem;"><dt class="pf-muted" x-text="'Листов (' + result.config.label + ')'"></dt><dd style="margin:0; font-weight:600;" x-text="formatNumber(result.sheets) + ' шт.'"></dd></div>
            <div style="display:flex; justify-content:space-between; gap:1rem;"><dt class="pf-muted">Материал</dt><dd style="margin:0; font-weight:600;" x-text="formatNumber(result.materialCost) + ' ₽'"></dd></div>
        </dl>
        <div style="margin-top:1rem; border:1px solid var(--pf-line); border-radius:12px; background:#f8fafc; padding:0.9rem 1rem;">
            <div class="pf-muted" style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em;">Итого ориентировочно</div>
            <div style="font-size:1.6rem; font-weight:700; margin-top:0.25rem;" x-text="formatNumber(result.total) + ' ₽'"></div>
        </div>
        <p class="pf-muted" style="font-size:0.85rem; margin-bottom:0;">Расчёт предварительный. Точная стоимость зависит от геометрии, покрытия и фактического замера.</p>
    </aside>
</section>

<style>
    [x-cloak] { display: none !important; }
    @media (min-width: 1100px) {
        .calc-layout { grid-template-columns: minmax(0,1fr) 360px; }
    }
</style>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
function roofCalculator() {
    const materials = {
        metalTile: { label: 'Металлочерепица', pricePerM2: 560, effectiveSheetAreaM2: 1.12 },
        sheet: { label: 'Профнастил', pricePerM2: 530, effectiveSheetAreaM2: 1.18 },
    };
    const roofs = [
        { id: 'f_1', label: 'Односкатная', icon: '/images/calculator/iconRoof/roof-type-1.png', fields: [{ code: 'w', label: 'Длина карниза, м' }, { code: 'h', label: 'Длина ската, м' }] },
        { id: 'f_2', label: 'Двускатная', icon: '/images/calculator/iconRoof/roof-type-2.png', fields: [{ code: 'w', label: 'Длина карниза, м' }, { code: 'h1', label: 'Длина ската 1, м' }, { code: 'h2', label: 'Длина ската 2, м' }] },
        { id: 'f_3', label: 'Вальмовая', icon: '/images/calculator/iconRoof/roof-type-3.png', fields: [{ code: 'w1', label: 'Длина карниза 1, м' }, { code: 'w2', label: 'Длина карниза 2, м' }, { code: 'w3', label: 'Длина конька, м' }, { code: 'h1', label: 'Длина ската 1, м' }, { code: 'h2', label: 'Длина ската 2, м' }] },
        { id: 'f_4', label: 'Мансардная', icon: '/images/calculator/iconRoof/roof-type-4.png', fields: [{ code: 'w', label: 'Длина карниза, м' }, { code: 'h1', label: 'Длина ската 1, м' }, { code: 'h2', label: 'Длина ската 2, м' }] },
        { id: 'f_5', label: 'Односкатная Г-образная', icon: '/images/calculator/iconRoof/roof-type-5.png', fields: [{ code: 'w1', label: 'Длина карниза 1, м' }, { code: 'w2', label: 'Длина карниза 2, м' }, { code: 'w3', label: 'Длина карниза 3, м' }, { code: 'w4', label: 'Длина карниза 4, м' }, { code: 'h1', label: 'Длина ската 1, м' }, { code: 'h2', label: 'Длина ската 2, м' }] },
        { id: 'f_6', label: 'Двускатная Г-образная', icon: '/images/calculator/iconRoof/roof-type-6.png', fields: [{ code: 'w1', label: 'Длина карниза 1, м' }, { code: 'w2', label: 'Длина конька 1, м' }, { code: 'w3', label: 'Длина карниза 2, м' }, { code: 'w4', label: 'Длина карниза 3, м' }, { code: 'w5', label: 'Длина конька 2, м' }, { code: 'w6', label: 'Длина карниза 4, м' }, { code: 'h1', label: 'Длина ската 1, м' }, { code: 'h2', label: 'Длина ската 2, м' }, { code: 'h3', label: 'Длина ската 3, м' }, { code: 'h4', label: 'Длина ската 4, м' }] },
        { id: 'f_7', label: 'Вальмовая Г-образная', icon: '/images/calculator/iconRoof/roof-type-7.png', fields: [{ code: 'w1', label: 'Длина карниза 1, м' }, { code: 'w2', label: 'Длина карниза 2, м' }, { code: 'w3', label: 'Длина конька 1, м' }, { code: 'w4', label: 'Длина карниза 3, м' }, { code: 'w5', label: 'Длина карниза 4, м' }, { code: 'w6', label: 'Длина карниза 5, м' }, { code: 'w7', label: 'Длина конька 2, м' }, { code: 'w8', label: 'Длина карниза 6, м' }, { code: 'h1', label: 'Длина ската 1, м' }, { code: 'h2', label: 'Длина ската 2, м' }, { code: 'h3', label: 'Длина ската 3, м' }, { code: 'h4', label: 'Длина ската 4, м' }, { code: 'h5', label: 'Длина ската 5, м' }, { code: 'h6', label: 'Длина ската 6, м' }] },
        { id: 'f_8', label: 'Мансардная Г-образная', icon: '/images/calculator/iconRoof/roof-type-8.png', fields: [{ code: 'w1', label: 'Длина карниза 1, м' }, { code: 'w2', label: 'Длина карнизной обратной 1, м' }, { code: 'w3', label: 'Длина конька 1, м' }, { code: 'w4', label: 'Длина карнизной обратной 2, м' }, { code: 'w5', label: 'Длина карниза 2, м' }, { code: 'w6', label: 'Длина карниза 3, м' }, { code: 'w7', label: 'Длина карнизной обратной 3, м' }, { code: 'w8', label: 'Длина конька 2, м' }, { code: 'w9', label: 'Длина карнизной обратной 4, м' }, { code: 'w10', label: 'Длина карниза 4, м' }, { code: 'h1', label: 'Длина ската 1, м' }, { code: 'h2', label: 'Длина ската 2, м' }, { code: 'h3', label: 'Длина ската 3, м' }, { code: 'h4', label: 'Длина ската 4, м' }, { code: 'h5', label: 'Длина ската 5, м' }, { code: 'h6', label: 'Длина ската 6, м' }, { code: 'h7', label: 'Длина ската 7, м' }, { code: 'h8', label: 'Длина ската 8, м' }] },
    ];

    const toNumber = (value) => {
        const n = Number(String(value || '').replace(',', '.').trim());
        return Number.isFinite(n) ? n : 0;
    };
    const average = (values) => {
        const filtered = values.filter((v) => v > 0);
        if (!filtered.length) return 0;
        return filtered.reduce((s, v) => s + v, 0) / filtered.length;
    };

    return {
        roofs,
        materials,
        roofType: 'f_2',
        material: 'metalTile',
        values: {},
        activeField: 'w',
        wastePercent: '10',
        withGutter: true,
        withInsulation: false,
        withOverhang: true,
        showResult: false,
        get currentRoof() {
            return this.roofs.find((r) => r.id === this.roofType) || this.roofs[0];
        },
        get schemeSrc() {
            return `/images/calculator/${this.roofType}/${String(this.activeField).toLowerCase()}.png`;
        },
        selectRoof(roof) {
            this.roofType = roof.id;
            this.activeField = roof.fields[0]?.code || 'w';
        },
        formatNumber(value) {
            return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 }).format(value || 0);
        },
        get result() {
            const get = (code) => toNumber(this.values[`${this.roofType}:${code}`] || '');
            const waste = Math.max(0, toNumber(this.wastePercent));
            const w = get('w'), h = get('h'), h1 = get('h1'), h2 = get('h2'), h3 = get('h3'), h4 = get('h4'), h5 = get('h5'), h6 = get('h6'), h7 = get('h7'), h8 = get('h8');
            const wValues = Array.from({ length: 10 }, (_, i) => get(`w${i + 1}`));
            const hValues = [h1, h2, h3, h4, h5, h6, h7, h8];
            const sumW = wValues.reduce((s, x) => s + x, 0);
            let baseArea = 0;
            if (this.roofType === 'f_1') baseArea = w * h;
            if (this.roofType === 'f_2') baseArea = w * (h1 + h2);
            if (this.roofType === 'f_3') baseArea = average([get('w1'), get('w2'), get('w3')]) * (h1 + h2);
            if (this.roofType === 'f_4') baseArea = w * (h1 + h2);
            if (this.roofType === 'f_5') baseArea = average([get('w1'), get('w2'), get('w3'), get('w4')]) * (h1 + h2);
            if (this.roofType === 'f_6') baseArea = average([get('w1'), get('w2'), get('w3'), get('w4'), get('w5'), get('w6')]) * (h1 + h2 + h3 + h4);
            if (this.roofType === 'f_7') baseArea = average(wValues) * average(hValues) * 6;
            if (this.roofType === 'f_8') baseArea = average(wValues) * average(hValues) * 8;
            const projectionArea = ['f_1', 'f_2', 'f_4'].includes(this.roofType)
                ? w * average([h1 || h, h2 || h])
                : average([sumW, baseArea / 2]);
            const withWasteArea = baseArea * (1 + waste / 100);
            const config = this.materials[this.material];
            const sheets = Math.ceil(withWasteArea / config.effectiveSheetAreaM2);
            const materialCost = withWasteArea * config.pricePerM2;
            const gutterCost = this.withGutter ? withWasteArea * 180 : 0;
            const insulationCost = this.withInsulation ? withWasteArea * 390 : 0;
            const overhangCost = this.withOverhang ? withWasteArea * 95 : 0;
            return {
                projectionArea, roofArea: baseArea, roofAreaWithWaste: withWasteArea, sheets, materialCost, config,
                total: materialCost + gutterCost + insulationCost + overhangCost,
            };
        },
    };
}
</script>
@endsection
