<template>

  <div class="chat-wrapper d-flex flex-column vh-100">
    <div class="flex-grow-1 overflow-auto p-4" ref="chatWindow" id="chatMessages">
      <div v-for="(message, index) in messages" :key="index" :class="['mb-3', message.role === 'user' ? 'text-end' : 'text-start']">
        <div :class="['d-inline-block p-3 rounded chat-bubble', message.role === 'user' ? 'user-bubble' : 'ai-bubble']">
          <strong>{{ message.role === 'user' ? 'Tu:' : 'AI:' }}</strong>
          <p class="mb-0">{{ message.content }}</p>
        </div>
      </div>
    </div>

    <div class="border-top p-3 d-flex input-area">
      <input
          v-model="userInput"
          @keyup.enter="sendMessage"
          type="text"
          class="form-control me-2"
          placeholder="Scrivi un messaggio..."
      />
      <button class="btn btn-success" @click="sendMessage">Invia</button>
    </div>
  </div>
</template>
<script>
export default {
  data() {
    return {
      userInput: '',
      messages: [],
      loading: false
    }
  },
  methods: {
    async sendMessage() {
      if (!this.userInput.trim()) return;

      // Aggiungi messaggio utente
      this.messages.push({ role: 'user', content: this.userInput });
      const userMessage = this.userInput;
      this.userInput = '';
      this.loading = true;

      this.scrollToBottom();

      try {
        // Chiamata API OpenAI
        const response = await fetch('https://api.openai.com/v1/chat/completions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer LA_TUA_API_KEY`
          },
          body: JSON.stringify({
            model: 'gpt-3.5-turbo',
            messages: this.messages.map(m => ({ role: m.role, content: m.content }))
          })
        });

        const data = await response.json();
        const aiReply = data.choices[0].message.content;

        // Aggiungi messaggio AI
        this.messages.push({ role: 'assistant', content: aiReply });
        this.scrollToBottom();
      } catch (error) {
        console.error('Errore nella chiamata OpenAI:', error);
      } finally {
        this.loading = false;
      }
    },
    scrollToBottom() {
      this.$nextTick(() => {
        const chatWindow = this.$refs.chatWindow;
        chatWindow.scrollTop = chatWindow.scrollHeight;
      });
    }
  }
}
</script>

<style scoped>
.chat-wrapper {
  background-color: #1e1e1e;
  color: #ffffff;
  width: 100vw;
  height: 100vh;
  margin: 0;
  padding: 0;
  position: fixed;
  top: 0;
  left: 0;
  box-sizing: border-box;
}
.chat-bubble {
  max-width: 70%;
  word-break: break-word;
}
.user-bubble {
  background-color: #0d6efd;
  color: white;
}
.ai-bubble {
  background-color: #2c2c2c;
  color: white;
}
.input-area {
  background-color: #2c2c2c;
  border-color: #444;
}
* {
  box-sizing: border-box;
}
body, html {
  overflow-x: hidden;
  margin: 0;
  padding: 0;
}
</style>

