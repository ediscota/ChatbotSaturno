import { OPEN_AI_KEY } from '../env.js';

export async function sendMessageToOpenAI(systemPrompt, messages) {
  const response = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${OPEN_AI_KEY}`
    },
    body: JSON.stringify({
      model: 'gpt-4o',
      messages: [systemPrompt, ...messages.map(m => ({ role: m.role, content: m.content }))],
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
  return await response.json();
}

export async function getQueryResult(query) {
  const response = await fetch('http://localhost/ChatbotSaturno/backend/public/api/query', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ query })
  });
  return await response.json();
}

export async function sendQueryToOpenAI(messages, tool, queryJson) {
  const response = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${OPEN_AI_KEY}`
    },
    body: JSON.stringify({
      model: 'gpt-4o',
      messages: [
        ...messages,
        { role: 'assistant', tool_calls: [tool] },
        { role: 'tool', tool_call_id: tool.id, content: queryJson }
      ]
    })
  });
  return await response.json();
}