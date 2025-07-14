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
import { sendMessageToOpenAI, getQueryResult, sendQueryToOpenAI } from '@/services/openaiService.js';
import { OPEN_AI_KEY } from '../env.js';
const prompt = `\`Sei un assistente SQL, (l'utilizzatore finale non sa nulla di programmazione quindi non far riferimento a sql, database o altro di tecnico)
        che genera query solo sulla base della seguente struttura del database.
        Usa sempre i nomi delle tabelle e delle colonne esattamente come indicati qui:
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
export default {
  data() {
    return {
      userInput: '',
      // prompt engineering
      systemPrompt: {
        role: 'system',
        content: prompt
      },
      messages: [],
      loading: false
    };
  },

  async beforeMount() {
     await this.fetchChats().then((chatId)=>{
       this.fetchMessages(chatId);
     })
    alert(chatId);
  },

  methods: {
    async fetchChats() {
      const response = await fetch('http://localhost/ChatbotSaturno/backend/public/api/chats');
      const data = await response.json();
      const chat = data.chats[0];
      //this.setChat(chat.id); da problemi questo, attualmente non ti serve salvarlo nella sessione quindi
      return chat.id;
    },

    async fetchMessages(chatId) {
      const res = await fetch(`http://localhost/ChatbotSaturno/backend/public/api/chats/${chatId}/messages`);
      const data = await res.json();
      this.messages = data.messages;
    },

    async sendMessage() {
      if (!this.userInput.trim()) return;
      const userMessage = { role: 'user', content: this.userInput };
      const chatId = this.$store.getters.currentChatId;
      // Salva messaggio utente su backend
      await fetch('http://localhost/ChatbotSaturno/backend/public/api/messages', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          chat_id: 1,
          role: 'user',
          content: userMessage.content
        })
      });

      this.messages.push(userMessage);
      this.userInput = '';
      this.loading = true;
      this.scrollToBottom();

      const data = await sendMessageToOpenAI(this.systemPrompt, this.messages);
      const message = data.choices[0].message;

      if (message.content) {
        const assistantMessage = { role: 'assistant', content: message.content };
        this.messages.push(assistantMessage);

        // Salva messaggio assistente su backend
        await fetch('http://localhost/ChatbotSaturno/backend/public/api/messages', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json' //
          },
          body: JSON.stringify({
            chat_id: 1,
            role: 'assistant',
            content: assistantMessage.content
          })
        });

        this.scrollToBottom();

      } else if (message.tool_calls && message.tool_calls.length > 0) {
        const tool = message.tool_calls[0];
        const toolArguments = JSON.parse(tool.function.arguments);
        const queryResult = await getQueryResult(toolArguments.query);
        const queryJson = JSON.stringify(queryResult.result);

        const newData = await sendQueryToOpenAI(this.messages, tool, queryJson);
        const aiFinalReply = newData.choices[0].message.content;

        const finalMessage = { role: 'assistant', content: aiFinalReply };
        this.messages.push(finalMessage);

        // Salva messaggio finale su backend
        await fetch('http://localhost/ChatbotSaturno/backend/public/api/messages', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json' //
          },
          body: JSON.stringify({
            chat_id: 1,
            role: 'assistant',
            content: finalMessage.content
          })
        });

        this.scrollToBottom();
      }

      this.loading = false;
    },

    scrollToBottom() {
      this.$nextTick(() => {
        const chatWindow = this.$refs.chatWindow;
        if (chatWindow) chatWindow.scrollTop = chatWindow.scrollHeight;
      });
    }
  }
};

</script>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
.chat-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  width: 100vw;
  background: linear-gradient(135deg, #000000 0%, #252525 100%);
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
  border: black;
  border-radius: 100%;
  background: linear-gradient(45deg, #ffffff 0%, #ffffff 100%);
  color: #454545;
  cursor: pointer;
  transition: all 0.4s ease;
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

