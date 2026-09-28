(() => {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const header = document.querySelector('.pf-header');
  const onScroll = () => {
    if (!header) return;
    header.classList.toggle('is-scrolled', window.scrollY > 12);
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  const burger = document.querySelector('[data-pf-burger]');
  const mobileNav = document.querySelector('[data-pf-mobile-nav]');
  const headerEl = document.querySelector('[data-pf-header]') || header;
  burger?.addEventListener('click', () => {
    const open = !(mobileNav?.classList.contains('is-open'));
    mobileNav?.classList.toggle('is-open', open);
    headerEl?.classList.toggle('is-open', open);
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    burger.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
  });
  mobileNav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      mobileNav.classList.remove('is-open');
      headerEl?.classList.remove('is-open');
      burger?.setAttribute('aria-expanded', 'false');
      burger?.setAttribute('aria-label', 'Открыть меню');
    });
  });

  initCatalogSide();
  initDetailGallery();

  if (reduce || typeof gsap === 'undefined') {
    document.querySelectorAll('.pf-reveal').forEach((el) => el.classList.add('is-in'));
    return;
  }

  gsap.registerPlugin(ScrollTrigger);

  gsap.utils.toArray('.pf-reveal').forEach((el, i) => {
    gsap.fromTo(
      el,
      { autoAlpha: 0, y: 36 },
      {
        autoAlpha: 1,
        y: 0,
        duration: 0.85,
        ease: 'power3.out',
        delay: Number(el.dataset.delay || 0) + (i % 4) * 0.04,
        scrollTrigger: {
          trigger: el,
          start: 'top 88%',
          once: true,
        },
      }
    );
  });

  const hero = document.querySelector('.pf-hero');
  if (hero) {
    gsap.from('.pf-hero-copy > *', {
      autoAlpha: 0,
      y: 40,
      duration: 1,
      stagger: 0.1,
      ease: 'power3.out',
      delay: 0.1,
    });
    gsap.from('.pf-metric', {
      autoAlpha: 0,
      y: 30,
      scale: 0.96,
      duration: 0.8,
      stagger: 0.08,
      ease: 'power3.out',
      delay: 0.35,
    });
    gsap.to('.pf-hero-orb-a', {
      y: 40,
      duration: 6,
      yoyo: true,
      repeat: -1,
      ease: 'sine.inOut',
    });
    gsap.to('.pf-hero-orb-b', {
      y: -30,
      duration: 5,
      yoyo: true,
      repeat: -1,
      ease: 'sine.inOut',
    });
    gsap.to('.pf-hero-grid', {
      yPercent: 8,
      ease: 'none',
      scrollTrigger: {
        trigger: hero,
        start: 'top top',
        end: 'bottom top',
        scrub: true,
      },
    });
  }

  gsap.utils.toArray('.pf-card').forEach((card) => {
    card.addEventListener('mouseenter', () => {
      gsap.to(card, { y: -5, duration: 0.35, ease: 'power2.out' });
    });
    card.addEventListener('mouseleave', () => {
      gsap.to(card, { y: 0, duration: 0.4, ease: 'power2.out' });
    });
  });

})();

function initDetailGallery() {
  const cover = document.querySelector('.pf-detail-cover img, [data-pf-cover]');
  if (!cover) return;

  const setCover = (src, activeBtn) => {
    if (!src) return;
    cover.setAttribute('src', src);
    document.querySelectorAll('.pf-detail-thumb').forEach((el) => el.classList.remove('is-active'));
    if (activeBtn?.classList.contains('pf-detail-thumb')) {
      activeBtn.classList.add('is-active');
    } else {
      const match = document.querySelector(`.pf-detail-thumb[data-pf-thumb="${CSS.escape(src)}"]`);
      match?.classList.add('is-active');
    }
  };

  document.querySelectorAll('[data-pf-thumb]').forEach((btn) => {
    btn.addEventListener('click', () => setCover(btn.getAttribute('data-pf-thumb'), btn));
  });
}

function initCatalogSide() {
  const side = document.querySelector('[data-pf-catalog-side]');
  if (!side) return;

  side.querySelectorAll('[data-pf-cat-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const node = toggle.closest('.pf-cat-node');
      if (!node) return;
      const open = !node.classList.contains('is-open');
      node.classList.toggle('is-open', open);
      node.classList.remove('is-match');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      const label = toggle.getAttribute('aria-label') || '';
      toggle.setAttribute(
        'aria-label',
        label.replace(/^(Свернуть|Развернуть)/, open ? 'Свернуть' : 'Развернуть')
      );
    });
  });

  const input = side.querySelector('[data-pf-catalog-search]');
  const results = side.querySelector('[data-pf-catalog-results]');
  const tree = side.querySelector('[data-pf-catalog-tree]');
  if (!input || !results || !tree) return;

  let timer = 0;
  const searchUrl = input.getAttribute('data-search-url') || '';
  const escapeHtml = (value) =>
    String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;');

  const filterTree = (query) => {
    const q = query.trim().toLowerCase();
    const nodes = tree.querySelectorAll('.pf-cat-node');
    if (!q) {
      nodes.forEach((node) => {
        node.classList.remove('is-filtered-out', 'is-match');
      });
      return;
    }

    nodes.forEach((node) => {
      const name = node.getAttribute('data-cat-name') || '';
      const selfMatch = name.includes(q);
      const childMatch = Array.from(node.querySelectorAll('.pf-cat-node')).some((child) =>
        (child.getAttribute('data-cat-name') || '').includes(q)
      );
      const match = selfMatch || childMatch;
      node.classList.toggle('is-filtered-out', !match);
      node.classList.toggle('is-match', match && q.length > 0);
    });
  };

  const renderResults = (payload) => {
    const categories = payload.categories || [];
    const products = payload.products || [];
    const items = [...categories, ...products];

    if (!items.length) {
      results.innerHTML = '<div class="pf-cat-search-empty">Ничего не найдено</div>';
      results.hidden = false;
      return;
    }

    results.innerHTML = items
      .map((item) => {
        const meta = item.sku ? `${item.type} · арт. ${item.sku}` : item.type;
        return `<a class="pf-cat-search-item" href="${escapeHtml(item.url)}"><strong>${escapeHtml(item.name)}</strong><span>${escapeHtml(meta)}</span></a>`;
      })
      .join('');
    results.hidden = false;
  };

  input.addEventListener('input', () => {
    const value = input.value || '';
    filterTree(value);
    window.clearTimeout(timer);

    if (value.trim().length < 2) {
      results.hidden = true;
      results.innerHTML = '';
      return;
    }

    timer = window.setTimeout(async () => {
      try {
        const res = await fetch(`${searchUrl}?q=${encodeURIComponent(value.trim())}`, {
          headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('search failed');
        renderResults(await res.json());
      } catch (_) {
        results.innerHTML = '<div class="pf-cat-search-empty">Ошибка поиска</div>';
        results.hidden = false;
      }
    }, 220);
  });
}
