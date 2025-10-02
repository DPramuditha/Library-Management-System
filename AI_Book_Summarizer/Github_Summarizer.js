import { config } from '../config.js';

const githubToken = config.githubToken;
const apiUrl = 'https://models.inference.ai.azure.com/chat/completions';
const chatDisplay = document.getElementById('chat-display');
const userInput = document.getElementById('user-input');

async function sendMessage() {
    const message = userInput.value.trim();
    if (!message) return;

    appendMessage('user', message);
    userInput.value = '';

    //loading animation
    const loadingId = showLoadingMessage();

    const messages = [
        {
            role: 'system',
            content: 'You are a helpful book summarizer assistant. Help users understand and summarize books. in Sinhala and English'
        },
        {
            role: 'user',
            content: message
        }
    ];

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${githubToken}`
            },
            body: JSON.stringify({
                model: 'gpt-4o',
                messages: messages,
                temperature: 0.7
            })
        });

        if (!response.ok) {
            const errorData = await response.text();
            throw new Error(`API error: ${response.status} - ${errorData}`);
        }

        const data = await response.json();

        // Remove loading animation
        removeLoadingMessage(loadingId);

        if (data.choices && data.choices.length > 0) {
            const botMessage = data.choices[0].message.content.trim();
            appendMessage('bot', botMessage);
        } else {
            appendMessage('bot', 'Error: No response from AI.');
        }
    } catch (error) {
        console.error('Error:', error);
        // Remove loading animation
        removeLoadingMessage(loadingId);

        appendMessage('bot', `Error: ${error.message}`);
    }
}
function showLoadingMessage() {
    const loadingId  = 'loading-' + Date.now();
    const chatContainer = document.createElement('div');
    chatContainer.id = loadingId;
    chatContainer.className = 'flex items-start space-x-3';
    chatContainer.innerHTML = `
        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
            <img src="animation_assets/artificial-intelligence.gif" alt="AI" class="h-8 w-8">
        </div>
        <div class="bg-white p-3 rounded-lg shadow-md max-w-md border border-gray-200 flex items-center">
            <img src="animation_assets/opportunities.gif" alt="Loading" class="h-10 w-10 mr-2">
            <p class="text-gray-800">AI is thinking...</p>
        </div>
    `;
    chatDisplay.appendChild(chatContainer);
    chatDisplay.scrollTop = chatDisplay.scrollHeight;
    return loadingId;
}

function removeLoadingMessage(loadingId) {
    const loadingElement = document.getElementById(loadingId);
    if (loadingElement) {
        loadingElement.remove();

    }
}

function appendMessage(sender, message) {
    const chatContainer = document.createElement('div');

    if (sender === 'user') {
        chatContainer.className = 'flex items-start space-x-3 justify-end';
        chatContainer.innerHTML = `
            <div class="bg-blue-600 text-white p-3 rounded-lg shadow-md max-w-md">
                <p>${message}</p>
            </div>
            <div class="w-8 h-8 bg-purple-700 rounded-full flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>


            </div>
        `;
    } else {
        chatContainer.className = 'flex items-start space-x-3';
        chatContainer.innerHTML = `
            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <img src="animation_assets/artificial-intelligence.gif" alt="AI" class="h-8 w-8">
            </div>
            <div class="bg-white p-3 rounded-lg shadow-sm max-w-md border border-gray-200">
                <p class="text-gray-800">${marked.parse(message)}</p>
            </div>
        `;
    }

    chatDisplay.appendChild(chatContainer);
    chatDisplay.scrollTop = chatDisplay.scrollHeight;
}

// Export the function to make it globally available
window.sendMessage = sendMessage;

// Add event listener for Enter key
userInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});