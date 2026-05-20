document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (!window.confirm(el.dataset.confirm || 'Confirmer ?')) {
        e.preventDefault();
      }
    });
  });

  const header = document.querySelector('.navbar');
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.nav-links');

  const syncHeader = () => {
    if (header) {
      header.classList.toggle('scrolled', window.scrollY > 24);
    }
  };

  const closeNav = () => {
    if (!toggle || !nav) {
      return;
    }

    nav.classList.remove('open');
    document.body.classList.remove('nav-open');
    toggle.setAttribute('aria-expanded', 'false');
  };

  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const isOpen = nav.classList.toggle('open');
      document.body.classList.toggle('nav-open', isOpen);
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 980) {
          closeNav();
        }
      });
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 980) {
        closeNav();
      }
    });
  }

  document.querySelectorAll('.typed-text[data-typed-items]').forEach((node) => {
    const rawItems = (node.dataset.typedItems || '').split('|').map((item) => item.trim()).filter(Boolean);
    if (rawItems.length <= 1) {
      if (rawItems.length === 1) {
        node.textContent = rawItems[0];
      }
      return;
    }

    const cursor = node.parentElement?.querySelector('.typed-cursor');
    let itemIndex = 0;
    let charIndex = rawItems[0].length;
    let isDeleting = false;

    node.textContent = rawItems[0];

    const tick = () => {
      const currentWord = rawItems[itemIndex] || '';

      if (isDeleting) {
        charIndex = Math.max(0, charIndex - 1);
      } else {
        charIndex = Math.min(currentWord.length, charIndex + 1);
      }

      node.textContent = currentWord.slice(0, charIndex);

      let delay = isDeleting ? 55 : 105;
      if (!isDeleting && charIndex === currentWord.length) {
        delay = 1400;
        isDeleting = true;
      } else if (isDeleting && charIndex === 0) {
        itemIndex = (itemIndex + 1) % rawItems.length;
        isDeleting = false;
        delay = 240;
      }

      window.setTimeout(tick, delay);
    };

    if (cursor) {
      cursor.setAttribute('data-ready', 'true');
    }

    window.setTimeout(tick, 900);
  });

  const revealItems = document.querySelectorAll('[data-reveal]');
  if (revealItems.length > 0) {
    document.body.classList.add('enhanced-ui');

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion || !('IntersectionObserver' in window)) {
      revealItems.forEach((item) => item.classList.add('is-visible'));
    } else {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) {
            return;
          }

          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        });
      }, {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px',
      });

      revealItems.forEach((item) => observer.observe(item));
    }
  }

  syncHeader();
  window.addEventListener('scroll', syncHeader, { passive: true });
});