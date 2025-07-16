<template>
  <div class="chat-page">
    <ChatSidebar ref="sidebar" />
    <Chat @chatCreated="onChatCreated" />
  </div>
</template>

<script>
import Chat from '@/components/Chat.vue'
import ChatSidebar from '@/components/ChatSidebar.vue'

export default {
  name: 'ChatView',
  components: {
    Chat,
    ChatSidebar
  },
  methods: {
    onChatCreated() {
      this.$refs.sidebar.fetchChats();
    }
  }
}
</script>

<style scoped>
.chat-page {
  display: flex;
  height: 100vh;
  width: 100vw;
  margin: 0;
  padding: 0;
  position: fixed;
  top: 0;
  left: 0;
  overflow: hidden;
}
</style>