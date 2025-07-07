<template>
  <div class="chat-container">
    <div class="messages-container" ref="chatWindow" id="chatMessages">
      <div class="messages-wrapper">
        <div v-for="(message, index) in messages" :key="index" class="message-row" :class="message.role">
          <div class="message-content">
            <div class="message-bubble" :class="message.role + '-bubble'">
              <div class="message-text">{{ message.content }}</div>
            </div>
          </div>
        </div>
        <div v-if="loading" class="message-row assistant">
          <div class="message-content">
            <div class="message-bubble assistant-bubble typing-bubble">
              <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="input-container">
      <div class="input-wrapper">
        <input
            v-model="userInput"
            @keyup.enter="sendMessage"
            type="text"
            class="message-input"
            placeholder="Scrivi un messaggio..."
            :disabled="loading"
        />
        <button
            class="send-button"
            @click="sendMessage"
            :disabled="!userInput.trim() || loading"
        >
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script>

import { OPEN_AI_KEY } from '../env.js';
export default {
  data() {
    return {
      userInput: '',
      //prompt engineering
      systemPrompt: {
        role: 'system',
        content: `\`Sei un assistente SQL che genera query solo sulla base della seguente struttura del database. Usa sempre i nomi delle tabelle e delle colonne esattamente come indicati qui:
          - Tabella: users
            - Colonne: id, name, username, email, phone, website, created_at, updated_at
          - Tabella: posts
            - Colonne: id, userId (foreign key -> users.id), title, body, created_at, updated_at
          - Tabella: comments
            - Colonne: id, postId (foreign key -> posts.id), name, email, body, created_at, updated_at
          Nota:
          - Ritorna la query corretta senza dare alcuna spiegazione. L'utilizzatore finale potrebbe non sapere nulla di programmazione
          - Usa sempre i nomi in inglese delle tabelle e delle colonne anche se l'utente usa termini diversi o sinonimi.
          - Se l'utente chiede i post di un utente, collegali tramite posts.userId = users.id.
          - Se l'utente chiede i commenti di un post, collegali tramite comments.postId = posts.id.\``
      },
      messages: [],
      loading: false
    }
  },
  methods: {
    async sendMessage() {
      if (!this.userInput.trim()) return;
      this.messages.push({ role: 'user', content: this.userInput });
      this.userInput = '';
      this.loading = true;
      this.scrollToBottom();
        //invia messaggio a server OpenAI
        const response = await fetch('https://api.openai.com/v1/chat/completions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${OPEN_AI_KEY}`
          },
          body: JSON.stringify({
            model: 'gpt-4o',
            messages: [
              this.systemPrompt,
              ...this.messages.map(m => ({ role: m.role, content: m.content }))
            ],
            tools: [
              {
                type: "function",
                function: {
                  name: "get_database_value",
                  description: "Ottieni dati specifici dal database.",
                  parameters: {
                    type: "object",
                    properties: {
                      query: {
                        type: "string",
                        description: "La query SQL da eseguire oppure una descrizione in linguaggio naturale dell'informazione richiesta."
                      }
                    },
                    required: ["query"]
                  }
                }
              }
            ],
            tool_choice: "auto"
          })
        });
        const data = await response.json();
        const message = data.choices[0].message;
        //risposta diretta
        if (message.content) {
          this.messages.push({ role: 'assistant', content: message.content });
          this.scrollToBottom();
        }
        //chiamata al tool
        else if (message.tool_calls && message.tool_calls.length > 0) {
          const toolCall = message.tool_calls[0];
          const toolArguments = JSON.parse(toolCall.function.arguments);
          const backendResponse = await fetch('http://localhost/ChatbotSaturno/backend/public/api/query', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ query: toolArguments.query })
          });
          const backendData = await backendResponse.json();
          const toolResult = JSON.stringify(backendData.result);
          //Passa il risultato della query al server opneai per "umanizzare" il messaggio
          const secondResponse = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${OPEN_AI_KEY}`
            },
            body: JSON.stringify({
              model: 'gpt-4o',
              messages: [
                ...this.messages,
                { role: 'assistant', tool_calls: [toolCall] },
                { role: 'tool', tool_call_id: toolCall.id, content: toolResult }
              ]
            })
          });
          const secondData = await secondResponse.json();
          const aiFinalReply = secondData.choices[0].message.content;
          this.messages.push({ role: 'assistant', content: aiFinalReply });
          this.scrollToBottom();
        }
        this.loading = false;
    },

    scrollToBottom() {
      this.$nextTick(() => { const chatWindow = this.$refs.chatWindow;chatWindow.scrollTop = chatWindow.scrollHeight; });
    }
  }
}
</script>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
.chat-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  width: 100vw;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  position: fixed;
  top: 0;
  left: 0;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.05);
}

.messages-wrapper {
  max-width: 800px;
  margin: 0 auto;
}

.message-row {
  display: flex;
  margin-bottom: 1rem;
  animation: slideIn 0.3s ease-out;
}

.message-row.user {
  justify-content: flex-end;
}

.message-row.ai {
  justify-content: flex-start;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.message-content {
  max-width: 70%;
}

.message-bubble {
  padding: 0.75rem 1rem;
  border-radius: 1.25rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  position: relative;
  backdrop-filter: blur(10px);
}

.user-bubble {
  background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom-right-radius: 0.3rem;
}

.ai-bubble {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-bottom-left-radius: 0.3rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.assistant-bubble {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border-bottom-left-radius: 0.3rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.message-text {
  line-height: 1.4;
  word-wrap: break-word;
}

.typing-bubble {
  padding: 1rem !important;
}

.typing-indicator {
  display: flex;
  gap: 0.3rem;
  align-items: center;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 50%;
  animation: typing 1.4s infinite ease-in-out;
}

.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
  0%, 80%, 100% {
    transform: scale(0);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

.input-container {
  padding: 1rem 1.5rem;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.input-wrapper {
  max-width: 800px;
  margin: 0 auto;
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.message-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 25px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 1rem;
  outline: none;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.message-input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.message-input:focus {
  border-color: rgba(255, 255, 255, 0.5);
  background: rgba(255, 255, 255, 0.15);
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
}

.send-button {
  width: 45px;
  height: 45px;
  border: none;
  border-radius: 50%;
  background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
  color: white;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.send-button:hover:not(:disabled) {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.send-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.send-button:active {
  transform: scale(0.95);
}

.messages-container::-webkit-scrollbar {
  width: 6px;
}

.messages-container::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.messages-container::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

@media (max-width: 768px) {
  .messages-container {
    padding: 0.75rem;
  }
  .input-container {
    padding: 0.75rem 1rem;
  }
  .message-content {
    max-width: 85%;
  }
}
* {
  box-sizing: border-box;
}

body, html {
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}
</style>

