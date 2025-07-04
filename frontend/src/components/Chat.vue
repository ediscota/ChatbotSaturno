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

import { OPEN_AI_KEY } from '../env.js';
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
      this.messages.push({ role: 'user', content: this.userInput });
      this.userInput = '';
      this.loading = true;

      this.scrollToBottom();

      try {
        const response = await fetch('https://api.openai.com/v1/chat/completions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${OPEN_AI_KEY}`
          },
          body: JSON.stringify({
            model: 'gpt-4o',
            messages: this.messages.map(m => ({ role: m.role, content: m.content })),
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

        // Caso 1: risposta diretta
        if (message.content) {
          this.messages.push({ role: 'assistant', content: message.content });
          this.scrollToBottom();
        }
        // Caso 2: chiamata al tool
        else if (message.tool_calls && message.tool_calls.length > 0) {
          const toolCall = message.tool_calls[0];
          const toolArguments = JSON.parse(toolCall.function.arguments);

          // Chiamata al backend Laravel per eseguire la query
          const backendResponse = await fetch('http://localhost/ChatbotSaturno/backend/public/api/query', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ query: toolArguments.query })
          });

          const backendData = await backendResponse.json();
          const toolResult = JSON.stringify(backendData.result);

          // Passa il risultato al modello
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
      } catch (error) {
        console.error('Errore nella chiamata OpenAI o al backend:', error);
      } finally {
        this.loading = false;
      }
    },

    //per modularizzare e scalare i tool
    async getDatabaseValue(query) {
      const response = await fetch('http://localhost:8000/api/query', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query }),
      });
      const data = await response.json();
      return data.result;
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

