<div class='chatbot'>
    <div class='chat-panel' id='portfolio-chatbot' aria-hidden='true' data-chat-panel data-chat-endpoint='<?= e(path_url('/api/v1/assistant/message')) ?>'>
        <div class='chat-head'>
            <div class='chat-head-copy'>
                <strong>Assistant de Cyrus-y</strong>
                <span>Profil, projets, compétences et certifications.</span>
            </div>
            <div class='chat-status' aria-label='Assistant disponible'>
                <span class='chat-status-dot' aria-hidden='true'></span>
                <span>En ligne</span>
            </div>
        </div>
        <div class='chat-body' role='log' aria-live='polite' aria-label="Conversation avec l'assistant" data-chat-body>
            <div class='chat-meta'>Discussion rapide</div>
            <div class='bubble assistant is-welcome'>Bonjour. Pose-moi une question sur le profil, les projets, les compétences ou les certifications.</div>
        </div>
        <form class='chat-form' data-chat-form>
            <label class='sr-only' for='portfolio-chatbot-message'>Votre question</label>
            <textarea class='input chat-input' id='portfolio-chatbot-message' name='message' placeholder='Écris ici ta question...' rows='1' maxlength='400' data-chat-input></textarea>
            <button class='btn' type='submit' data-chat-submit>
                <i class='bi bi-send-fill' aria-hidden='true'></i>
                <span>Envoyer</span>
            </button>
        </form>
    </div>
    <button class='btn chat-toggle' type='button' aria-controls='portfolio-chatbot' aria-expanded='false' data-chat-toggle>
        <i class='bi bi-stars' aria-hidden='true'></i>
        <span>Assistant</span>
    </button>
</div>