document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('textarea[data-autogrow]').forEach((textarea) => {
    const grow = () => {
      textarea.style.height = 'auto';
      textarea.style.height = `${textarea.scrollHeight}px`;
    };
    textarea.addEventListener('input', grow);
    grow();
  });

  document.querySelectorAll('[data-preview-target]').forEach((input) => {
    input.addEventListener('change', () => {
      const targetId = input.getAttribute('data-preview-target');
      const target = targetId ? document.getElementById(targetId) : null;
      const files = Array.from(input.files || []);
      if (!target || files.length === 0) {
        return;
      }

      if (input.hasAttribute('multiple')) {
        target.innerHTML = '';
        files.forEach((file) => {
          if (!file.type.startsWith('image/')) {
            return;
          }

          const reader = new FileReader();
          reader.onload = () => {
            const img = document.createElement('img');
            img.src = String(reader.result || '');
            img.alt = file.name;
            img.style.height = '160px';
            img.style.width = '100%';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '16px';
            target.appendChild(img);
          };
          reader.readAsDataURL(file);
        });
        return;
      }

      const [file] = files;
      if (!file.type.startsWith('image/')) {
        return;
      }

      const reader = new FileReader();
      reader.onload = () => {
        target.src = String(reader.result || '');
        target.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  });

  document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
    const textareaId = editor.getAttribute('data-rich-editor');
    const textarea = textareaId ? document.getElementById(textareaId) : null;
    if (!textarea) {
      return;
    }

    const sync = () => {
      textarea.value = editor.innerHTML.trim();
    };

    editor.addEventListener('input', sync);
    editor.closest('form')?.addEventListener('submit', sync);

    document.querySelectorAll(`[data-editor-toolbar='${textareaId}'] [data-editor-action]`).forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.getAttribute('data-editor-action');
        editor.focus();
        if (action === 'createLink') {
          const url = window.prompt('URL du lien');
          if (url) {
            document.execCommand('createLink', false, url);
            sync();
          }
          return;
        }
        if (action === 'insertImage') {
          const url = window.prompt('URL de l image');
          if (url) {
            document.execCommand('insertImage', false, url);
            sync();
          }
          return;
        }

        if (action) {
          document.execCommand(action, false);
          sync();
        }
      });
    });
  });

  const themeForm = document.querySelector('[data-theme-editor]');
  const themePreview = document.querySelector('[data-theme-preview]');
  const hexToRgba = (value, alpha) => {
    const hex = String(value || '').trim().replace('#', '');
    if (!/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test(hex)) {
      return value;
    }

    const normalized = hex.length === 3
      ? hex.split('').map((char) => char + char).join('')
      : hex;
    const red = Number.parseInt(normalized.slice(0, 2), 16);
    const green = Number.parseInt(normalized.slice(2, 4), 16);
    const blue = Number.parseInt(normalized.slice(4, 6), 16);
    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
  };

  if (themeForm && themePreview) {
    const defaults = {
      primary: '#ff4d4f',
      secondary: '#141414',
      accent: '#ff8f90',
      background: '#0b0b0b',
      text: '#f2ede8',
      displayFont: 'Mulish, Segoe UI, sans-serif',
      bodyFont: 'Roboto, Segoe UI, sans-serif',
    };

    const applyPreview = () => {
      const data = new FormData(themeForm);
      const primary = String(data.get('primary_color') || defaults.primary);
      const secondary = String(data.get('secondary_color') || defaults.secondary);
      const accent = String(data.get('accent_color') || defaults.accent);
      const background = String(data.get('background_color') || defaults.background);
      const text = String(data.get('text_color') || defaults.text);
      const displayFont = String(data.get('display_font_family') || defaults.displayFont);
      const bodyFont = String(data.get('body_font_family') || defaults.bodyFont);

      themePreview.style.background = `linear-gradient(135deg, ${secondary} 0%, ${background} 56%, ${primary} 100%)`;
      themePreview.style.color = text;
      themePreview.style.borderColor = hexToRgba(text, 0.08);
      themePreview.style.boxShadow = `0 24px 50px ${hexToRgba(background, 0.42)}`;

      const previewShell = themePreview.querySelector('[data-preview-shell]');
      if (previewShell) {
        previewShell.style.background = hexToRgba(text, 0.04);
        previewShell.style.borderBottomColor = hexToRgba(text, 0.08);
      }

      themePreview.querySelectorAll('[data-preview-display]').forEach((node) => {
        node.style.fontFamily = displayFont;
        node.style.color = text;
      });

      themePreview.querySelectorAll('[data-preview-body]').forEach((node) => {
        node.style.fontFamily = bodyFont;
      });

      themePreview.querySelectorAll('[data-preview-muted]').forEach((node) => {
        node.style.color = hexToRgba(text, 0.74);
      });

      themePreview.querySelectorAll('[data-preview-accent]').forEach((node) => {
        node.style.color = primary;
        node.style.background = hexToRgba(primary, 0.12);
        node.style.border = `1px solid ${hexToRgba(primary, 0.22)}`;
        node.style.display = 'inline-flex';
        node.style.alignItems = 'center';
        node.style.padding = '6px 12px';
        node.style.borderRadius = '999px';
        node.style.letterSpacing = '0.08em';
      });

      const primaryButton = themePreview.querySelector('[data-preview-primary]');
      if (primaryButton) {
        primaryButton.style.background = `linear-gradient(135deg, ${primary}, ${accent})`;
        primaryButton.style.border = `1px solid ${hexToRgba(primary, 0.28)}`;
        primaryButton.style.color = '#ffffff';
      }

      const ghostButton = themePreview.querySelector('[data-preview-ghost]');
      if (ghostButton) {
        ghostButton.style.background = hexToRgba(text, 0.04);
        ghostButton.style.border = `1px solid ${hexToRgba(text, 0.12)}`;
        ghostButton.style.color = text;
      }

      themePreview.querySelectorAll('[data-preview-surface]').forEach((node) => {
        node.style.background = hexToRgba(text, 0.04);
        node.style.border = `1px solid ${hexToRgba(text, 0.08)}`;
        node.style.color = text;
      });

      themePreview.querySelectorAll('[data-preview-chip]').forEach((node) => {
        node.style.background = hexToRgba(primary, 0.12);
        node.style.border = `1px solid ${hexToRgba(primary, 0.22)}`;
        node.style.color = primary;
      });
    };

    themeForm.addEventListener('input', applyPreview);
    themeForm.addEventListener('change', applyPreview);
    applyPreview();
  }

  document.querySelectorAll('[data-collaboration-toggle]').forEach((toggle) => {
    const container = toggle.closest('form')?.querySelector('[data-collaboration-fields]');
    if (!container) {
      return;
    }

    const inputs = Array.from(container.querySelectorAll('[data-collaboration-input]'));
    const sync = () => {
      const enabled = String(toggle.value || '0') === '1';
      container.hidden = !enabled;
      inputs.forEach((input) => {
        input.disabled = !enabled;
      });
    };

    toggle.addEventListener('change', sync);
    sync();
  });

  const geoMap = document.getElementById('analytics-geo-map');
  if (geoMap) {
    let countries = [];
    try {
      countries = JSON.parse(geoMap.dataset.countries || '[]');
    } catch (_error) {
      countries = [];
    }

    const fallback = () => {
      geoMap.innerHTML = '';
      if (countries.length === 0) {
        geoMap.textContent = 'Aucune donnee geographique exploitable pour le moment.';
        return;
      }

      const list = document.createElement('div');
      list.className = 'stack-list';
      countries.forEach((item) => {
        const card = document.createElement('div');
        card.className = 'mini-card';

        const title = document.createElement('strong');
        title.textContent = String(item.country || 'Inconnu');

        const meta = document.createElement('p');
        meta.className = 'meta';
        meta.textContent = `${Number(item.total || 0)} visite(s)`;

        card.appendChild(title);
        card.appendChild(meta);
        list.appendChild(card);
      });
      geoMap.appendChild(list);
    };

    const renderGeoChart = () => {
      if (!window.google || !window.google.visualization || countries.length === 0) {
        fallback();
        return;
      }

      const rootStyles = window.getComputedStyle(document.documentElement);
      const primaryColor = rootStyles.getPropertyValue('--primary').trim() || '#ff4d4f';
      const accentColor = rootStyles.getPropertyValue('--accent').trim() || '#ff8f90';
      const secondaryColor = rootStyles.getPropertyValue('--secondary').trim() || '#141414';
      const data = [['Country', 'Visits']];
      countries.forEach((item) => data.push([String(item.country_code || item.country || 'XX'), Number(item.total || 0)]));
      const table = window.google.visualization.arrayToDataTable(data);
      const chart = new window.google.visualization.GeoChart(geoMap);
      chart.draw(table, {
        colorAxis: { colors: [accentColor, primaryColor] },
        backgroundColor: 'transparent',
        datalessRegionColor: secondaryColor,
        legend: 'none',
      });
    };

    const script = document.createElement('script');
    script.src = 'https://www.gstatic.com/charts/loader.js';
    script.onload = () => {
      if (!window.google || !window.google.charts) {
        fallback();
        return;
      }

      window.google.charts.load('current', { packages: ['geochart'] });
      window.google.charts.setOnLoadCallback(renderGeoChart);
    };
    script.onerror = fallback;
    document.head.appendChild(script);
  }
});
