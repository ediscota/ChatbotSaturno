<template>
  <div class="chat-sidebar">
    <h2>Le tue chat</h2>
    <ul>
      <li
          v-for="chat in chats"
          :key="chat.id"
          @click="selectChat(chat.id)"
          :class="{ active: chat.id === currentChatId }"
      >
        {{ chat.title }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  name: 'ChatSidebar',

  data() {
    return {
      chats: []
    }
  },

  computed: {
    currentChatId() {
      return this.$store.state.currentChatId;
    }
  },

  methods: {
    async fetchChats() {
        const response = await fetch('http://localhost/ChatbotSaturno/backend/public/api/chats')
        const data = await response.json()
        this.chats = data.chats
      },

    async selectChat(chatId) {
      await this.$store.dispatch('setChat', chatId)
    }
  },

  async mounted() {
    await this.fetchChats()
  }
}
</script>

<style scoped>
.chat-sidebar {
  width: 250px;
  height: 100vh;
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
  padding: 1rem;
  border-right: 1px solid rgba(255, 255, 255, 0.2);
  box-sizing: border-box;
  overflow-y: auto;
  flex-shrink: 0; /* Impedisce alla sidebar di ridursi */
}

.chat-sidebar h2 {
  color: white;
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.2rem;
  font-weight: 600;
}

.chat-sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.chat-sidebar li {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  transition: all 0.2s ease;
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.chat-sidebar li:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(5px);
}

.chat-sidebar li.active {
  background: rgba(255, 255, 255, 0.2);
  font-weight: bold;
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.2);
}



.chat-sidebar::-webkit-scrollbar {
  width: 6px;
}

.chat-sidebar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
}

.chat-sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
}

.chat-sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}
</style>
