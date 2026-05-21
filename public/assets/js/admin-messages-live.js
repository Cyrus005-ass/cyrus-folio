document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('[data-live-messages]');
  if (!root) {
    return;
  }

  const body = root.querySelector('[data-live-messages-body]');
  const status = root.querySelector('[data-live-status]');
  const sync = root.querySelector('[data-live-last-sync]');
  const summary = root.querySelector('[data-live-summary]');
  const refresh = root.querySelector('[data-live-refresh]');
  const endpoint = String(root.dataset.liveEndpoint || '');
  const base = String(root.dataset.adminBase || '').replace(/\/$/, '');
  const csrf = String(root.dataset.csrf || '');
  const delay = Math.max(15000, Number.parseInt(root.dataset.liveInterval || '30000', 10) || 30000);

  if (!body || !status || !sync || !summary || !refresh || !endpoint || !base || !csrf) {
    return;
  }

  let busy = false;
  let timer = 0;
  let knownIds = new Set(
    Array.from(body.querySelectorAll('[data-message-id]'))
      .map((row) => String(row.dataset.messageId || '').trim())
      .filter(Boolean),
  );
  let hasFetched = false;

  const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  });

  const escapeHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

  const safeBase = escapeHtml(base);
  const safeCsrf = escapeHtml(csrf);

  const excerpt = (value, length = 90) => {
    const text = String(value ?? '').replace(/\s+/g, ' ').trim();
    if (text.length <= length) {
      return text;
    }

    return `${text.slice(0, Math.max(1, length - 3))}...`;
  };

  const parseTimestamp = (value) => {
    const text = String(value ?? '').trim();
    if (text === '') {
      return Number.NaN;
    }

    const normalized = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(text)
      ? text.replace(' ', 'T')
      : text;

    return Date.parse(normalized);
  };

  const formatDate = (value) => {
    const time = parseTimestamp(value);
    if (Number.isNaN(time)) {
      const fallback = String(value ?? '').trim();
      return fallback !== '' ? fallback : '-';
    }

    return dateFormatter.format(new Date(time));
  };

  const setBadge = (node, text, tone = '') => {
    node.textContent = text;
    node.className = tone ? `badge ${tone}` : 'badge';
  };

  const markBusy = (value) => {
    busy = value;
    refresh.disabled = value;
    refresh.textContent = value ? 'Actualisation...' : 'Rafra?chir';
  };

  const sourceBadge = (message) => {
    const source = String(message.source || '').toLowerCase();
    if (source === 'merged') {
      return "<span class='badge blue'>Archive + Live</span>";
    }

    if (source === 'firestore' || source === 'firebase') {
      return "<span class='badge blue'>Live</span>";
    }

    return "<span class='badge'>Archive</span>";
  };

  const stateBadge = (message) => {
    return String(message.statut || 'nouveau').toLowerCase() === 'lu'
      ? "<span class='badge green'>Lu</span>"
      : "<span class='badge red'>Non lu</span>";
  };

  const actionMarkup = (message) => {
    const id = encodeURIComponent(String(message.id || '').trim());
    const email = String(message.email || '').trim();
    const subject = encodeURIComponent(`Re: ${String(message.sujet || '').trim()}`);
    const unread = String(message.statut || 'nouveau').toLowerCase() !== 'lu';
    const readAction = `${safeBase}/${id}/read`;
    const deleteAction = `${safeBase}/${id}`;
    const detailUrl = `${safeBase}/${id}`;
    const mailtoUrl = escapeHtml(`mailto:${encodeURIComponent(email)}?subject=${subject}`);

    return [
      "<div class='actions'>",
      `<a class='btn ghost' href='${detailUrl}'>Lire</a>`,
      `<a class='btn ghost' href='${mailtoUrl}'>R?pondre</a>`,
      unread
        ? [
          `<form method='post' action='${readAction}'>`,
          `<input type='hidden' name='_csrf' value='${safeCsrf}'>`,
          "<input type='hidden' name='_method' value='PUT'>",
          "<button class='btn ghost' type='submit'>Marquer lu</button>",
          '</form>',
        ].join('')
        : '',
      [
        `<form method='post' action='${deleteAction}'>`,
        `<input type='hidden' name='_csrf' value='${safeCsrf}'>`,
        "<input type='hidden' name='_method' value='DELETE'>",
        "<button class='btn danger' type='submit' data-confirm='Supprimer ce message ?'>Supprimer</button>",
        '</form>',
      ].join(''),
      '</div>',
    ].join('');
  };

  const rowMarkup = (message, newIds) => {
    const id = String(message.id || '').trim();
    const unread = String(message.statut || 'nouveau').toLowerCase() !== 'lu';
    const classes = [
      unread ? 'is-unread' : '',
      newIds.has(id) ? 'is-new-live' : '',
    ].filter(Boolean).join(' ');
    const name = String(message.nom || 'Sans nom').trim() || 'Sans nom';
    const email = String(message.email || '').trim();
    const subject = String(message.sujet || '').trim();
    const preview = excerpt(message.message || '');

    return [
      `<tr${classes !== '' ? ` class='${classes}'` : ''} data-message-id='${escapeHtml(id)}'>`,
      `<td><strong>${escapeHtml(name)}</strong><div class='meta'>${escapeHtml(email)}</div></td>`,
      `<td>${escapeHtml(subject)}<div class='meta'>${escapeHtml(preview)}</div></td>`,
      `<td>${escapeHtml(formatDate(message.created_at))}</td>`,
      `<td>${stateBadge(message)}</td>`,
      `<td>${sourceBadge(message)}</td>`,
      `<td>${actionMarkup(message)}</td>`,
      '</tr>',
    ].join('');
  };

  const bindConfirmHandlers = () => {
    root.querySelectorAll('[data-confirm]').forEach((button) => {
      if (button.dataset.confirmBound === '1') {
        return;
      }

      button.addEventListener('click', (event) => {
        if (!window.confirm(button.dataset.confirm || 'Confirmer ?')) {
          event.preventDefault();
        }
      });
      button.dataset.confirmBound = '1';
    });
  };

  const render = (payload) => {
    const messages = Array.isArray(payload.data) ? payload.data : [];
    const currentIds = new Set(
      messages
        .map((message) => String((message && message.id) || '').trim())
        .filter(Boolean),
    );
    const newIds = new Set();

    if (hasFetched || knownIds.size > 0) {
      currentIds.forEach((id) => {
        if (!knownIds.has(id)) {
          newIds.add(id);
        }
      });
    }

    if (messages.length === 0) {
      body.innerHTML = "<tr><td colspan='6'><div class='empty'>Aucun message.</div></td></tr>";
    } else {
      body.innerHTML = messages.map((message) => rowMarkup(message, newIds)).join('');
    }

    knownIds = currentIds;
    hasFetched = true;
    bindConfirmHandlers();

    const archiveCount = Number(payload.archive_count || 0);
    const liveCount = Number(payload.live_count || 0);
    const liveEnabled = Boolean(payload.live_enabled);

    if (liveEnabled) {
      summary.textContent = `${messages.length} message(s) affich?s. ${archiveCount} archive(s) MySQL et ${liveCount} ?l?ment(s) live Firestore consult?s.`;
      setBadge(status, 'Flux fusionn? actif', 'blue');
    } else {
      summary.textContent = `${messages.length} message(s) affich?s depuis l'archive MySQL. Firestore est d?sactiv? ou non configur? pour ce module.`;
      setBadge(status, 'Archive uniquement');
    }

    const lastSync = formatDate(payload.last_sync_at || new Date().toISOString());
    setBadge(sync, `Derni?re synchro ${lastSync}`);
  };

  const schedule = () => {
    if (timer) {
      window.clearTimeout(timer);
    }

    timer = window.setTimeout(() => {
      if (!document.hidden) {
        void refreshMessages(true);
        return;
      }

      schedule();
    }, delay);
  };

  const refreshMessages = async (silent = false) => {
    if (busy) {
      return;
    }

    markBusy(true);
    if (!silent) {
      setBadge(status, 'Actualisation en cours', 'blue');
    }

    try {
      const response = await window.fetch(endpoint, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
        },
      });

      let payload = null;
      try {
        payload = await response.json();
      } catch (_error) {
        payload = null;
      }

      if (!response.ok || !payload || payload.success !== true) {
        const message = payload && typeof payload.message === 'string' && payload.message.trim() !== ''
          ? payload.message.trim()
          : `Erreur HTTP ${response.status}`;
        throw new Error(message);
      }

      render(payload);
    } catch (error) {
      const message = error instanceof Error && error.message.trim() !== ''
        ? error.message.trim()
        : 'Impossible de r?cup?rer les messages.';
      setBadge(status, 'Synchro indisponible', 'red');
      setBadge(sync, 'Derni?re synchro indisponible');
      summary.textContent = message;
    } finally {
      markBusy(false);
      schedule();
    }
  };

  refresh.addEventListener('click', () => {
    void refreshMessages(false);
  });

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      void refreshMessages(true);
    }
  });

  void refreshMessages(true);
});
