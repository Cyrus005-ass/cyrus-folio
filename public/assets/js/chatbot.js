document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-chat-toggle]');
  const panel = document.querySelector('[data-chat-panel]');
  const form = document.querySelector('[data-chat-form]');
  const input = document.querySelector('[data-chat-input]');
  const body = document.querySelector('[data-chat-body]');
  const submit = document.querySelector('[data-chat-submit]');

  if (!toggle || !panel || !form || !input || !body || !submit) {
    return;
  }

  const history = [];
  const endpoint = panel.dataset.chatEndpoint || '/api/v1/chatbot/message';

  const scrollToBottom = () => {
    body.scrollTop = body.scrollHeight;
  };

  const syncExpandedState = (isOpen) => {
    panel.classList.toggle('open', isOpen);
    panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  };

  const autoResize = () => {
    input.style.height = 'auto';
    input.style.height = `${Math.min(input.scrollHeight, 140)}px`;
  };

  const setBusy = (busy) => {
    panel.classList.toggle('is-busy', busy);
    input.disabled = busy;
    submit.disabled = busy;
  };

  const pushHistory = (role, content) => {
    const text = String(content || '').trim();
    if (!text) {
      return;
    }

    history.push({ role, content: text });
    while (history.length > 10) {
      history.shift();
    }
  };

  const addBubble = (text, me = false, track = true, pending = false) => {
    const bubble = document.createElement('div');
    bubble.className = 'bubble ' + (me ? 'me' : 'assistant') + (pending ? ' is-pending' : '');
    bubble.textContent = text;
    body.appendChild(bubble);
    if (track) {
      pushHistory(me ? 'user' : 'assistant', text);
    }
    scrollToBottom();
    return bubble;
  };

  toggle.addEventListener('click', () => {
    const isOpen = !panel.classList.contains('open');
    syncExpandedState(isOpen);
    if (isOpen) {
      input.focus();
      autoResize();
      scrollToBottom();
    }
  });

  input.addEventListener('input', autoResize);
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        submit.click();
      }
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const message = input.value.trim();
    if (!message) {
      return;
    }

    addBubble(message, true);
    input.value = '';
    autoResize();

    const pending = addBubble('Je prepare une reponse...', false, false, true);
    setBusy(true);

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          message,
          history,
        }),
      });

      let data = null;
      try {
        data = await response.json();
      } catch (_error) {
        data = null;
      }

      pending.remove();
      setBusy(false);
      input.focus();

      if (!response.ok) {
        addBubble((data && data.message) || 'Le chatbot ne repond pas correctement pour le moment.');
        return;
      }

      addBubble((data && data.answer) || 'Reponse indisponible.');
    } catch (_error) {
      pending.remove();
      setBusy(false);
      input.focus();
      addBubble('Impossible de joindre le chatbot pour le moment.');
    }
  });

  syncExpandedState(false);
  autoResize();
});
