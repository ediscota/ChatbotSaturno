import { createStore } from 'vuex'

export default createStore({
    state: {
        currentChatId: null
    },
    mutations: {
        setChatId(state, chatId) {
            state.currentChatId = chatId;
        }
    },
    actions: {
        setChat({ commit }, chatId) {
            commit('setChatId', chatId);
        }
    },
    getters: {
        currentChatId: state => state.currentChatId
    }
});