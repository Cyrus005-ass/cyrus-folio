document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-chat-toggle]');
  const panel = document.querySelector('[data-chat-panel]');
  const form = document.querySelector('[data-chat-form]');
  const input = document.querySelector('[data-chat-input]');
  const body = document.querySelector('[data-chat-body]');

  if (!toggle || !panel || !form || !input || !body) {
    return;
  }

  const history = [];

  const scrollToBottom = () => {
    body.scrollTop = body.scrollHeight;
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

  const addBubble = (text, me = false, track = true) => {
    const bubble = document.createElement('div');
    bubble.className = 'bubble' + (me ? ' me' : '');
    bubble.textContent = text;
    body.appendChild(bubble);
    if (track) {
      pushHistory(me ? 'user' : 'assistant', text);
    }
    scrollToBottom();
    return bubble;
  };

  toggle.addEventListener('click', () => {
    const isOpen = panel.classList.toggle('open');
    if (isOpen) {
      input.focus();
      scrollToBottom();
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
    input.focus();

    const pending = addBubble('Je reflechis...', false, false);

    try {
      const response = await fetch(window.APP_URL + '/api/v1/chatbot/message', {
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

      if (!response.ok) {
        addBubble('Le chatbot ne repond pas correctement pour le moment.');
        return;
      }

      addBubble((data && data.answer) || 'Reponse indisponible.');
    } catch (_error) {
      pending.remove();
      addBubble('Impossible de joindre le chatbot pour le moment.');
    }
  });
});