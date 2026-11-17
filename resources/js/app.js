import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT || 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    document.addEventListener('livewire:init', () => {
        const userId = Number(document.body.dataset.userId || 0);
        if (!userId) return;
        window.Echo.private(`chat.user.${userId}`).listen('.chat.message.sent', (message) => {
            if (window.Livewire) Livewire.dispatch('chat-message-received', {
                conversationId: Number(message.conversation_id), messageId: Number(message.id),
            });
            if (Number(message.sender_id) !== userId) Livewire.dispatch('chat-external-alert', message);
        }).listen('.chat.call.signal', (signal) => {
            if (Number(signal.sender_id) !== userId) {
                window.dispatchEvent(new CustomEvent('next-hr-call-signal', { detail: signal }));
            }
        });
    });
}

let chatAudioContext = null;

document.addEventListener('pointerdown', () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return;
    chatAudioContext ??= new AudioContextClass();
    if (chatAudioContext.state === 'suspended') chatAudioContext.resume();
}, { once: true });

function playChatTone() {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;
        chatAudioContext ??= new AudioContextClass();
        const context = chatAudioContext;
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = 880;
        gain.gain.setValueAtTime(0.0001, context.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.12, context.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.18);
        oscillator.connect(gain);
        gain.connect(context.destination);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.2);
    } catch { /* Le navigateur peut bloquer l'audio avant une interaction utilisateur. */ }
}

const alertedChatMessageIds = new Set();

function alertForChatMessage(messageId, title, body, url, conversationId = '') {
    if (messageId && alertedChatMessageIds.has(messageId)) return;
    if (messageId) {
        alertedChatMessageIds.add(messageId);
        if (alertedChatMessageIds.size > 200) alertedChatMessageIds.clear();
    }
    playChatTone();
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    const notification = new Notification(title || 'Next HR', {
        body: body || 'Nouveau message',
        tag: `next-hr-chat-${conversationId || url || ''}`,
        icon: '/favicon.ico',
    });
    notification.onclick = () => {
        window.focus();
        if (url) window.location.assign(url);
        notification.close();
    };
}

window.enableChatNotifications = async () => {
    if ('Notification' in window && Notification.permission === 'default') {
        await Notification.requestPermission();
    }
};

let callConversationId = null;
let callPeer = null;
let callLocalStream = null;
let pendingOffer = null;
let pendingIce = [];

function callUi(show, title = 'Appel', status = '') {
    const dock = document.getElementById('chat-call-dock');
    if (!dock) return;
    dock.classList.toggle('hidden', !show);
    dock.classList.toggle('flex', show);
    document.getElementById('chat-call-title').textContent = title;
    document.getElementById('chat-call-status').textContent = status;
}

function sendCallSignal(type, payload = {}) {
    if (callConversationId) Livewire.dispatch('send-chat-call-signal', {
        conversationId: callConversationId, type, payload,
    });
}

function createPeer() {
    const dock = document.getElementById('chat-call-dock');
    let extraServers = [];
    try { extraServers = JSON.parse(dock?.dataset.iceServers || '[]'); } catch { /* configuration optional */ }
    const peer = new RTCPeerConnection({
        iceServers: [{ urls: ['stun:stun.l.google.com:19302'] }, ...extraServers],
    });
    callLocalStream.getTracks().forEach((track) => peer.addTrack(track, callLocalStream));
    peer.onicecandidate = ({ candidate }) => {
        if (candidate) sendCallSignal('candidate', candidate.toJSON());
    };
    peer.ontrack = ({ streams }) => {
        document.getElementById('chat-call-remote').srcObject = streams[0];
        document.getElementById('chat-call-status').textContent = 'Appel en cours';
    };
    peer.onconnectionstatechange = () => {
        if (peer.connectionState === 'connected') document.getElementById('chat-call-status').textContent = 'Appel en cours';
        if (peer.connectionState === 'failed') document.getElementById('chat-call-status').textContent = 'Connexion impossible. Vérifiez le réseau ou le serveur TURN.';
    };
    return peer;
}

async function getCallMedia() {
    if (!navigator.mediaDevices?.getUserMedia) throw new Error('Les appels nécessitent HTTPS ou localhost et un navigateur compatible.');
    callLocalStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
    document.getElementById('chat-call-local').srcObject = callLocalStream;
}

window.startChatCall = async (conversationId, name) => {
    try {
        callConversationId = Number(conversationId);
        callUi(true, `Appel avec ${name}`, 'Autorisez le micro et la caméra…');
        document.getElementById('chat-call-incoming-actions').classList.add('hidden');
        await getCallMedia();
        callPeer = createPeer();
        const offer = await callPeer.createOffer();
        await callPeer.setLocalDescription(offer);
        sendCallSignal('offer', { sdp: offer.sdp });
        document.getElementById('chat-call-status').textContent = 'Appel en cours…';
    } catch (error) {
        callUi(true, 'Impossible de démarrer l’appel', error.message || 'Vérifiez les permissions du navigateur.');
        document.getElementById('chat-call-incoming-actions').classList.remove('hidden');
    }
};

window.acceptChatCall = async () => {
    if (!pendingOffer) return;
    try {
        await getCallMedia();
        callPeer = createPeer();
        await callPeer.setRemoteDescription({ type: 'offer', sdp: pendingOffer.sdp });
        for (const candidate of pendingIce) await callPeer.addIceCandidate(candidate);
        pendingIce = [];
        const answer = await callPeer.createAnswer();
        await callPeer.setLocalDescription(answer);
        sendCallSignal('answer', { sdp: answer.sdp });
        pendingOffer = null;
        document.getElementById('chat-call-incoming-actions').classList.add('hidden');
        document.getElementById('chat-call-status').textContent = 'Connexion…';
    } catch (error) {
        callUi(true, 'Impossible de répondre', error.message || 'Vérifiez les permissions du navigateur.');
    }
};

window.rejectChatCall = () => { sendCallSignal('reject'); finishCall(); };
window.endChatCall = () => { sendCallSignal('hangup'); finishCall(); };

function finishCall() {
    callPeer?.close();
    callLocalStream?.getTracks().forEach((track) => track.stop());
    callPeer = null;
    callLocalStream = null;
    pendingOffer = null;
    pendingIce = [];
    document.getElementById('chat-call-local')?.removeAttribute('srcObject');
    if (document.getElementById('chat-call-local')) document.getElementById('chat-call-local').srcObject = null;
    if (document.getElementById('chat-call-remote')) document.getElementById('chat-call-remote').srcObject = null;
    document.getElementById('chat-call-incoming-actions')?.classList.add('hidden');
    callUi(false);
    callConversationId = null;
}

window.addEventListener('next-hr-call-signal', async ({ detail: signal }) => {
    if (signal.type === 'offer') {
        if (callConversationId && callConversationId !== Number(signal.conversation_id)) {
            Livewire.dispatch('send-chat-call-signal', { conversationId: signal.conversation_id, type: 'reject', payload: {} });
            return;
        }
        callConversationId = Number(signal.conversation_id);
        pendingOffer = signal.payload;
        callUi(true, `Appel entrant de ${signal.sender_name}`, 'Votre collègue vous appelle…');
        document.getElementById('chat-call-incoming-actions').classList.remove('hidden');
        return;
    }
    if (Number(signal.conversation_id) !== callConversationId) return;
    if (signal.type === 'answer' && callPeer) {
        await callPeer.setRemoteDescription({ type: 'answer', sdp: signal.payload.sdp });
        for (const candidate of pendingIce) await callPeer.addIceCandidate(candidate);
        pendingIce = [];
    } else if (signal.type === 'candidate') {
        const candidate = new RTCIceCandidate(signal.payload);
        if (callPeer?.remoteDescription) await callPeer.addIceCandidate(candidate);
        else pendingIce.push(candidate);
    } else if (signal.type === 'hangup' || signal.type === 'reject') {
        document.getElementById('chat-call-status').textContent = signal.type === 'reject' ? 'Appel refusé' : 'Appel terminé';
        finishCall();
    }
});

document.addEventListener('livewire:init', () => {
    Livewire.on('chat-sent', () => playChatTone());
    Livewire.on('chat-external-alert', (event) => {
        alertForChatMessage(event.id, event.sender_name, event.body || event.attachment_name,
            event.url, event.conversation_id);
    });
    Livewire.on('chat-alert', (event) => {
        alertForChatMessage(event.messageId, event.title, event.body, event.url);
    });
});
